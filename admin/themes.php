<?php
require __DIR__ . '/_init.php';
require_can('content');
$activeNav = 'themes';

$edit = $_GET['edit'] ?? null;
$row = null;
if ($edit !== null && $edit !== 'new') {
    $row = q('SELECT * FROM themes WHERE id = ?', [(int) $edit])->fetch();
    if (!$row) {
        flash('danger', 'Theme not found.');
        redirect('themes.php');
    }
}
$categories = tour_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $target = q('SELECT * FROM themes WHERE id = ?', [(int) ($_POST['id'] ?? 0)])->fetch();

    if ($action === 'toggle' && $target) {
        q('UPDATE themes SET is_active = 1 - is_active WHERE id = ?', [$target['id']]);
        flash('success', '"' . $target['name'] . '" updated.');
        redirect('themes.php');
    }
    if ($action === 'delete' && $target) {
        q('DELETE FROM themes WHERE id = ?', [$target['id']]);
        flash('success', '"' . $target['name'] . '" deleted.');
        redirect('themes.php');
    }

    if ($action !== 'save') {
        redirect('themes.php');
    }

    $cat = (string) ($_POST['link_category'] ?? '');
    $data = [
        trim((string) ($_POST['name'] ?? '')),
        preg_replace('/[^a-z0-9\- ]/i', '', trim((string) ($_POST['icon'] ?? ''))) ?: 'fa-star',
        in_array($cat, $categories, true) ? $cat : '',
        !empty($_POST['is_active']) ? 1 : 0,
        (int) ($_POST['sort_order'] ?? 0),
    ];
    if ($data[0] === '') {
        flash('danger', 'Please enter a name.');
        redirect('themes.php?edit=' . ($row ? $row['id'] : 'new'));
    }
    if ($row) {
        q('UPDATE themes SET name = ?, icon = ?, link_category = ?, is_active = ?, sort_order = ? WHERE id = ?', array_merge($data, [$row['id']]));
    } else {
        q('INSERT INTO themes (name, icon, link_category, is_active, sort_order) VALUES (?, ?, ?, ?, ?)', $data);
    }
    flash('success', 'Theme saved.');
    redirect('themes.php');
}

if ($edit !== null) {
    $row = $row ?: ['id' => 0, 'name' => '', 'icon' => 'fa-star', 'link_category' => '', 'is_active' => 1, 'sort_order' => 0];
    $adminTitle = $row['id'] ? 'Edit theme' : 'New theme';
    require __DIR__ . '/_header.php';
    ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= e($adminTitle) ?></h1>
        <a href="themes.php" class="btn btn-sm btn-light">All themes</a>
    </div>
    <form method="post" class="card shadow mb-4" style="max-width:820px">
        <div class="card-body">
            <?= csrf_field() ?>
            <div class="form-group"><label>Name *</label><input class="form-control" name="name" value="<?= e($row['name']) ?>" required></div>
            <div class="form-group">
                <label>Icon</label>
                <div class="input-group" style="max-width:360px">
                    <div class="input-group-prepend"><span class="input-group-text"><i id="iconPrev" class="fas <?= e($row['icon']) ?>"></i></span></div>
                    <input class="form-control" name="icon" value="<?= e($row['icon']) ?>" data-icon-input="iconPrev">
                </div>
                <small class="form-text text-muted">A Font Awesome icon name, e.g. <code>fa-landmark</code>. <a href="https://fontawesome.com/search?o=r&m=free&s=solid" target="_blank" rel="noopener">Browse icons</a></small>
            </div>
            <div class="form-group" style="max-width:360px">
                <label>Opens tours in category</label>
                <select class="form-control" name="link_category">
                    <option value="">All tours</option>
                    <?php foreach ($categories as $c): ?><option<?= $row['link_category'] === $c ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="max-width:200px"><label>Display order</label><input type="number" class="form-control" name="sort_order" value="<?= (int) $row['sort_order'] ?>"></div>
            <?= switch_field('is_active', 'Show on website', (bool) $row['is_active']) ?>
            <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            <a href="themes.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
    <?php
    require __DIR__ . '/_footer.php';
    exit;
}

$adminTitle = 'Home page themes';
$rows = q('SELECT * FROM themes ORDER BY sort_order, id')->fetchAll();
require __DIR__ . '/_header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Home page themes</h1>
        <p class="text-muted small mb-0">The row of icons under the home page hero.</p>
    </div>
    <a href="themes.php?edit=new" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm"></i> New theme</a>
</div>
<div class="card shadow mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th></th><th>Name</th><th>Links to</th><th>Status</th><th>Order</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><i class="fas <?= e($r['icon']) ?> icon-preview"></i></td>
                    <td><a href="themes.php?edit=<?= (int) $r['id'] ?>" class="font-weight-bold"><?= e($r['name']) ?></a></td>
                    <td><?= e($r['link_category'] ?: 'All tours') ?></td>
                    <td><?= status_badge((bool) $r['is_active']) ?></td>
                    <td><?= (int) $r['sort_order'] ?></td>
                    <td class="text-right text-nowrap">
                        <a href="themes.php?edit=<?= (int) $r['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-pen"></i></a>
                        <?= post_button('themes.php', ['action' => 'toggle', 'id' => $r['id']], '<i class="fas ' . ($r['is_active'] ? 'fa-eye-slash' : 'fa-check') . '"></i>', 'btn-secondary') ?>
                        <?= post_button('themes.php', ['action' => 'delete', 'id' => $r['id']], '<i class="fas fa-trash"></i>', 'btn-danger', 'Delete this theme?') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
