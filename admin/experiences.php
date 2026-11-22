<?php
require __DIR__ . '/_init.php';
require_can('content');
$activeNav = 'experiences';

$edit = $_GET['edit'] ?? null;
$row = null;
if ($edit !== null && $edit !== 'new') {
    $row = q('SELECT * FROM experiences WHERE id = ?', [(int) $edit])->fetch();
    if (!$row) {
        flash('danger', 'Experience not found.');
        redirect('experiences.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $target = q('SELECT * FROM experiences WHERE id = ?', [(int) ($_POST['id'] ?? 0)])->fetch();

    if ($action === 'toggle' && $target) {
        q('UPDATE experiences SET is_active = 1 - is_active WHERE id = ?', [$target['id']]);
        flash('success', '"' . $target['name'] . '" updated.');
        redirect('experiences.php');
    }
    if ($action === 'delete' && $target) {
        q('DELETE FROM experiences WHERE id = ?', [$target['id']]);
        flash('success', '"' . $target['name'] . '" deleted.');
        redirect('experiences.php');
    }

    if ($action !== 'save') {
        redirect('experiences.php');
    }

    $data = [
        trim((string) ($_POST['name'] ?? '')),
        preg_replace('/[^a-z0-9\- ]/i', '', trim((string) ($_POST['icon'] ?? ''))) ?: 'fa-star',
        trim((string) ($_POST['description'] ?? '')),
        !empty($_POST['is_active']) ? 1 : 0,
        (int) ($_POST['sort_order'] ?? 0),
    ];
    if ($data[0] === '') {
        flash('danger', 'Please enter a name.');
        redirect('experiences.php?edit=' . ($row ? $row['id'] : 'new'));
    }
    if ($row) {
        q('UPDATE experiences SET name = ?, icon = ?, description = ?, is_active = ?, sort_order = ? WHERE id = ?', array_merge($data, [$row['id']]));
    } else {
        q('INSERT INTO experiences (name, icon, description, is_active, sort_order) VALUES (?, ?, ?, ?, ?)', $data);
    }
    flash('success', 'Experience saved.');
    redirect('experiences.php');
}

if ($edit !== null) {
    $row = $row ?: ['id' => 0, 'name' => '', 'icon' => 'fa-star', 'description' => '', 'is_active' => 1, 'sort_order' => 0];
    $adminTitle = $row['id'] ? 'Edit experience' : 'New experience';
    require __DIR__ . '/_header.php';
    ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= e($adminTitle) ?></h1>
        <a href="experiences.php" class="btn btn-sm btn-light">All experiences</a>
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
                <small class="form-text text-muted">A Font Awesome icon name, e.g. <code>fa-leaf</code>. <a href="https://fontawesome.com/search?o=r&m=free&s=solid" target="_blank" rel="noopener">Browse icons</a></small>
            </div>
            <div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="3"><?= e($row['description']) ?></textarea></div>
            <div class="form-group" style="max-width:200px"><label>Display order</label><input type="number" class="form-control" name="sort_order" value="<?= (int) $row['sort_order'] ?>"></div>
            <?= switch_field('is_active', 'Show on website', (bool) $row['is_active']) ?>
            <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            <a href="experiences.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
    <?php
    require __DIR__ . '/_footer.php';
    exit;
}

$adminTitle = 'Experiences';
$rows = q('SELECT * FROM experiences ORDER BY sort_order, id')->fetchAll();
require __DIR__ . '/_header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Experiences</h1>
    <a href="experiences.php?edit=new" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm"></i> New experience</a>
</div>
<div class="card shadow mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th></th><th>Name</th><th>Description</th><th>Status</th><th>Order</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><i class="fas <?= e($r['icon']) ?> icon-preview"></i></td>
                    <td><a href="experiences.php?edit=<?= (int) $r['id'] ?>" class="font-weight-bold"><?= e($r['name']) ?></a></td>
                    <td><small class="text-muted"><?= e(mb_strimwidth((string) $r['description'], 0, 90, '…')) ?></small></td>
                    <td><?= status_badge((bool) $r['is_active']) ?></td>
                    <td><?= (int) $r['sort_order'] ?></td>
                    <td class="text-right text-nowrap">
                        <a href="experiences.php?edit=<?= (int) $r['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-pen"></i></a>
                        <?= post_button('experiences.php', ['action' => 'toggle', 'id' => $r['id']], '<i class="fas ' . ($r['is_active'] ? 'fa-eye-slash' : 'fa-check') . '"></i>', 'btn-secondary') ?>
                        <?= post_button('experiences.php', ['action' => 'delete', 'id' => $r['id']], '<i class="fas fa-trash"></i>', 'btn-danger', 'Delete this experience?') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
