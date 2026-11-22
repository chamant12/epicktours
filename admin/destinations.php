<?php
require __DIR__ . '/_init.php';
require_can('content');
$activeNav = 'destinations';

$edit = $_GET['edit'] ?? null; // id, "new" or null for the list
$row = null;
if ($edit !== null && $edit !== 'new') {
    $row = q('SELECT * FROM destinations WHERE id = ?', [(int) $edit])->fetch();
    if (!$row) {
        flash('danger', 'Destination not found.');
        redirect('destinations.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $target = q('SELECT * FROM destinations WHERE id = ?', [(int) ($_POST['id'] ?? 0)])->fetch();

    if ($action === 'toggle' && $target) {
        q('UPDATE destinations SET is_active = 1 - is_active WHERE id = ?', [$target['id']]);
        flash('success', '"' . $target['name'] . '" updated.');
        redirect('destinations.php');
    }
    if ($action === 'delete' && $target) {
        delete_image($target['image']);
        q('DELETE FROM destinations WHERE id = ?', [$target['id']]);
        flash('success', '"' . $target['name'] . '" deleted.');
        redirect('destinations.php');
    }

    if ($action !== 'save') {
        redirect('destinations.php');
    }

    // Save (create or update)
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $active = !empty($_POST['is_active']) ? 1 : 0;
    $order = (int) ($_POST['sort_order'] ?? 0);

    if ($name === '') {
        flash('danger', 'Please enter a name.');
        redirect('destinations.php?edit=' . ($row ? $row['id'] : 'new'));
    }

    try {
        $image = handle_image_field('image', $row['image'] ?? '', 'destination');
    } catch (RuntimeException $ex) {
        $image = $row['image'] ?? '';
        flash('danger', 'Image: ' . $ex->getMessage());
    }

    if ($row) {
        q('UPDATE destinations SET name = ?, description = ?, image = ?, is_active = ?, sort_order = ? WHERE id = ?', [$name, $description, $image, $active, $order, $row['id']]);
        $id = $row['id'];
    } else {
        // The slug links tours to this destination, so it is fixed once created
        $slug = $base = slugify($name);
        for ($n = 2; q('SELECT id FROM destinations WHERE slug = ?', [$slug])->fetch(); $n++) {
            $slug = $base . '-' . $n;
        }
        q('INSERT INTO destinations (slug, name, description, image, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?)', [$slug, $name, $description, $image, $active, $order]);
        $id = db()->lastInsertId();
    }
    flash('success', 'Destination saved.');
    redirect('destinations.php?edit=' . $id);
}

// ----- Edit form -----
if ($edit !== null) {
    $row = $row ?: ['id' => 0, 'slug' => '', 'name' => '', 'description' => '', 'image' => '', 'is_active' => 1, 'sort_order' => 0];
    $adminTitle = $row['id'] ? 'Edit destination' : 'New destination';
    require __DIR__ . '/_header.php';
    ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= e($adminTitle) ?></h1>
        <a href="destinations.php" class="btn btn-sm btn-light">All destinations</a>
    </div>
    <form method="post" enctype="multipart/form-data" class="card shadow mb-4" style="max-width:820px">
        <div class="card-body">
            <?= csrf_field() ?>
            <div class="form-group"><label>Name *</label><input class="form-control" name="name" value="<?= e($row['name']) ?>" required></div>
            <?php if ($row['slug']): ?><p class="small text-muted">Link: <code>tours.php?dest=<?= e($row['slug']) ?></code></p><?php endif; ?>
            <div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="3"><?= e($row['description']) ?></textarea><small class="form-text text-muted">Shown when visitors hover over the card.</small></div>
            <?= image_field('image', 'Image', $row['image'], 'destination') ?>
            <div class="form-group" style="max-width:200px"><label>Display order</label><input type="number" class="form-control" name="sort_order" value="<?= (int) $row['sort_order'] ?>"></div>
            <?= switch_field('is_active', 'Show on website', (bool) $row['is_active']) ?>
            <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            <a href="destinations.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
    <?php
    require __DIR__ . '/_footer.php';
    exit;
}

// ----- List -----
$adminTitle = 'Destinations';
$rows = q('SELECT * FROM destinations ORDER BY sort_order, id')->fetchAll();
require __DIR__ . '/_header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Destinations</h1>
    <a href="destinations.php?edit=new" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm"></i> New destination</a>
</div>
<div class="card shadow mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th></th><th>Name</th><th>Description</th><th>Status</th><th>Order</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?php if ($r['image']): ?><img src="<?= e(img_url($r['image'], 'small')) ?>" class="thumb-sm" alt=""><?php else: ?><span class="thumb-sm d-inline-block"></span><?php endif; ?></td>
                    <td><a href="destinations.php?edit=<?= (int) $r['id'] ?>" class="font-weight-bold"><?= e($r['name']) ?></a></td>
                    <td><small class="text-muted"><?= e(mb_strimwidth((string) $r['description'], 0, 90, '…')) ?></small></td>
                    <td><?= status_badge((bool) $r['is_active']) ?></td>
                    <td><?= (int) $r['sort_order'] ?></td>
                    <td class="text-right text-nowrap">
                        <a href="destinations.php?edit=<?= (int) $r['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-pen"></i></a>
                        <?= post_button('destinations.php', ['action' => 'toggle', 'id' => $r['id']], '<i class="fas ' . ($r['is_active'] ? 'fa-eye-slash' : 'fa-check') . '"></i>', 'btn-secondary') ?>
                        <?= post_button('destinations.php', ['action' => 'delete', 'id' => $r['id']], '<i class="fas fa-trash"></i>', 'btn-danger', 'Delete this destination?') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
