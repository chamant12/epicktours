<?php
require __DIR__ . '/_init.php';
require_can('content');
$activeNav = 'pages';

$pageKey = (string) ($_GET['page'] ?? '');
$page = $PAGE_FIELDS[$pageKey] ?? null;

// ----- Save -----
if ($page && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = q('SELECT bkey, value FROM page_blocks WHERE page = ?', [$pageKey])->fetchAll(PDO::FETCH_KEY_PAIR);
    $errors = [];
    foreach ($page['sections'] as $fields) {
        foreach ($fields as $key => $f) {
            if ($f['type'] === 'image') {
                try {
                    $value = handle_image_field($key, $current[$key] ?? '', $f['spec']);
                } catch (RuntimeException $ex) {
                    $errors[] = $f['label'] . ': ' . $ex->getMessage();
                    continue;
                }
            } else {
                $value = trim((string) ($_POST[$key] ?? ''));
            }
            q('INSERT INTO page_blocks (page, bkey, value, updated_by, updated_at) VALUES (?, ?, ?, ?, NOW())
               ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by = VALUES(updated_by), updated_at = NOW()',
              [$pageKey, $key, $value, current_user()['id']]);
        }
    }
    foreach ($errors as $err) {
        flash('danger', $err);
    }
    flash('success', $errors ? 'Saved, except the images listed above.' : $page['title'] . ' saved.');
    redirect('pages.php?page=' . urlencode($pageKey));
}

// ----- Edit one page -----
if ($page) {
    $adminTitle = 'Edit: ' . $page['title'];
    $values = q('SELECT bkey, value FROM page_blocks WHERE page = ?', [$pageKey])->fetchAll(PDO::FETCH_KEY_PAIR);
    require __DIR__ . '/_header.php';
    ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= e($page['title']) ?></h1>
        <div>
            <a href="../<?= e($page['url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View page</a>
            <a href="pages.php" class="btn btn-sm btn-light">All pages</a>
        </div>
    </div>
    <p class="text-muted small">Empty fields show the default text.</p>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php foreach ($page['sections'] as $section => $fields): ?>
            <div class="card shadow mb-4 section-card">
                <div class="card-header py-3"><h6 class="font-weight-bold text-primary"><?= e($section) ?></h6></div>
                <div class="card-body">
                    <?php foreach ($fields as $key => $f): ?>
                        <?php
                        $value = $values[$key] ?? null;
                        if ($value === null || ($value === '' && $f['type'] !== 'image')) {
                            $value = $f['default'];
                        }
                        ?>
                        <?= cms_field($key, $f, (string) $value) ?>
                        <?php if (str_ends_with($key, '_icon')): ?>
                            <small class="d-block text-muted mt-n2 mb-3">Preview: <i id="ip_<?= e($key) ?>" class="fas <?= e($value) ?> icon-preview"></i> · Find icons at <a href="https://fontawesome.com/search?o=r&m=free&s=solid" target="_blank" rel="noopener">fontawesome.com</a></small>
                            <script>document.getElementById('f_<?= e($key) ?>').setAttribute('data-icon-input', 'ip_<?= e($key) ?>');</script>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="mb-5">
            <button class="btn btn-primary"><i class="fas fa-save"></i> Save changes</button>
            <a href="pages.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
    <?php
    require __DIR__ . '/_footer.php';
    exit;
}

// ----- List of pages -----
$adminTitle = 'Pages';
$updated = q('SELECT page, MAX(updated_at) AS at FROM page_blocks GROUP BY page')->fetchAll(PDO::FETCH_KEY_PAIR);
require __DIR__ . '/_header.php';
?>
<h1 class="h3 mb-2 text-gray-800">Pages</h1>
<p class="mb-4 text-muted">Edit the text and images on each page. Tours, destinations and experiences have their own sections in the menu.</p>

<div class="card shadow mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Page</th><th>Editable fields</th><th>Last edited</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($PAGE_FIELDS as $key => $p): ?>
                <tr>
                    <td><a href="pages.php?page=<?= e($key) ?>" class="font-weight-bold"><?= e($p['title']) ?></a></td>
                    <td><?= array_sum(array_map('count', $p['sections'])) ?></td>
                    <td><small class="text-muted"><?= isset($updated[$key]) ? e(date('d M Y, H:i', strtotime($updated[$key]))) : 'Never' ?></small></td>
                    <td class="text-right">
                        <a href="pages.php?page=<?= e($key) ?>" class="btn btn-sm btn-primary"><i class="fas fa-pen"></i> Edit</a>
                        <a href="../<?= e($p['url']) ?>" target="_blank" class="btn btn-sm btn-light"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
