<?php
require __DIR__ . '/_init.php';
require_can('content');
$adminTitle = 'Tours';
$activeNav = 'tours';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $tour = q('SELECT * FROM tours WHERE id = ?', [$id])->fetch();
    if ($tour) {
        switch ($_POST['action'] ?? '') {
            case 'toggle':
                q('UPDATE tours SET is_active = 1 - is_active, updated_at = NOW() WHERE id = ?', [$id]);
                flash('success', '"' . $tour['title'] . '" is now ' . ($tour['is_active'] ? 'hidden from' : 'shown on') . ' the website.');
                break;
            case 'delete':
                foreach (q('SELECT path FROM tour_images WHERE tour_id = ?', [$id])->fetchAll(PDO::FETCH_COLUMN) as $p) {
                    delete_image($p);
                }
                delete_image($tour['image']);
                q('DELETE FROM tours WHERE id = ?', [$id]);
                q('DELETE FROM page_blocks WHERE page = ?', ['tour-' . $id]); // its SEO fields
                flash('success', '"' . $tour['title'] . '" deleted.');
                break;
        }
    }
    redirect('tours.php');
}

$tours = q('SELECT t.*, (SELECT COUNT(*) FROM tour_images i WHERE i.tour_id = t.id) AS photos FROM tours t ORDER BY sort_order, id')->fetchAll();
require __DIR__ . '/_header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Tours</h1>
    <a href="tour-edit.php" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm"></i> New tour</a>
</div>

<div class="card shadow mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th></th><th>Tour</th><th>Category</th><th>Length</th><th>From</th><th>Status</th><th>Order</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($tours as $t): ?>
                <tr>
                    <td><?php if ($t['image']): ?><img src="<?= e(img_url($t['image'], 'small')) ?>" class="thumb-sm" alt=""><?php else: ?><span class="thumb-sm d-inline-block"></span><?php endif; ?></td>
                    <td>
                        <a href="tour-edit.php?id=<?= (int) $t['id'] ?>" class="font-weight-bold"><?= e($t['title']) ?></a>
                        <?php if ($t['is_featured']): ?><span class="badge badge-warning ml-1">Featured</span><?php endif; ?>
                        <br><small class="text-muted"><?= (int) $t['photos'] + ($t['image'] ? 1 : 0) ?> photo(s)</small>
                    </td>
                    <td><?= e($t['category']) ?></td>
                    <td><?= (int) $t['days'] ?>D / <?= (int) $t['nights'] ?>N</td>
                    <td><?= e(price($t['price'])) ?></td>
                    <td><?= status_badge((bool) $t['is_active']) ?></td>
                    <td><?= (int) $t['sort_order'] ?></td>
                    <td class="text-right text-nowrap">
                        <a href="tour-edit.php?id=<?= (int) $t['id'] ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-pen"></i></a>
                        <a href="../tour.php?slug=<?= e($t['slug']) ?>" target="_blank" class="btn btn-sm btn-light" title="View"><i class="fas fa-eye"></i></a>
                        <?= post_button('tours.php', ['action' => 'toggle', 'id' => $t['id']], '<i class="fas ' . ($t['is_active'] ? 'fa-eye-slash' : 'fa-check') . '"></i>', 'btn-secondary') ?>
                        <?= post_button('tours.php', ['action' => 'delete', 'id' => $t['id']], '<i class="fas fa-trash"></i>', 'btn-danger', 'Delete this tour and all its photos? This cannot be undone.') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$tours): ?><tr><td colspan="8" class="text-center text-muted py-4">No tours yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
