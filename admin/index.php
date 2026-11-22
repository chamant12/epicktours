<?php
require __DIR__ . '/_init.php';
$adminTitle = 'Dashboard';
$activeNav = 'dashboard';

$counts = [
    'tours'        => (int) q('SELECT COUNT(*) FROM tours WHERE is_active = 1')->fetchColumn(),
    'destinations' => (int) q('SELECT COUNT(*) FROM destinations WHERE is_active = 1')->fetchColumn(),
    'experiences'  => (int) q('SELECT COUNT(*) FROM experiences WHERE is_active = 1')->fetchColumn(),
];
if (can('enquiries')) {
    $counts['new_enquiries'] = (int) q("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();
    $counts['month_enquiries'] = (int) q("SELECT COUNT(*) FROM enquiries WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
    $recentEnquiries = q('SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 6')->fetchAll();
}
if (can('users')) {
    $counts['users'] = (int) q('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
}
$recentTours = q('SELECT id, title, category, image, updated_at, created_at FROM tours ORDER BY COALESCE(updated_at, created_at) DESC LIMIT 5')->fetchAll();

function stat_card(string $label, $value, string $icon, string $color, string $href): string
{
    return '<div class="col-xl-3 col-md-6 mb-4"><a href="' . $href . '" class="text-decoration-none"><div class="card border-left-' . $color . ' shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2">'
        . '<div class="text-xs font-weight-bold text-' . $color . ' text-uppercase mb-1">' . e($label) . '</div>'
        . '<div class="h5 mb-0 font-weight-bold text-gray-800">' . e($value) . '</div></div>'
        . '<div class="col-auto"><i class="fas ' . $icon . ' fa-2x text-gray-300"></i></div></div></div></div></a></div>';
}

require __DIR__ . '/_header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Welcome, <?= e(current_user()['name']) ?></h1>
    <a href="tour-edit.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> New tour</a>
</div>

<div class="row">
    <?= stat_card('Active tours', $counts['tours'], 'fa-route', 'primary', 'tours.php') ?>
    <?= stat_card('Destinations', $counts['destinations'], 'fa-map-location-dot', 'success', 'destinations.php') ?>
    <?php if (can('enquiries')): ?>
        <?= stat_card('New enquiries', $counts['new_enquiries'], 'fa-envelope', 'warning', 'enquiries.php?status=new') ?>
        <?= stat_card('Enquiries this month', $counts['month_enquiries'], 'fa-calendar', 'info', 'enquiries.php') ?>
    <?php else: ?>
        <?= stat_card('Experiences', $counts['experiences'], 'fa-star', 'info', 'experiences.php') ?>
        <?= stat_card('Editable pages', count($PAGE_FIELDS), 'fa-file-lines', 'warning', 'pages.php') ?>
    <?php endif; ?>
</div>

<div class="row">
    <?php if (can('enquiries')): ?>
    <div class="col-lg-7 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Latest enquiries</h6>
                <a href="enquiries.php" class="small">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Name</th><th>Travel</th><th>Received</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($recentEnquiries as $q): ?>
                        <tr>
                            <td><a href="enquiry.php?id=<?= (int) $q['id'] ?>"><?= e($q['name']) ?></a><br><small class="text-muted"><?= e($q['country']) ?></small></td>
                            <td><small><?= $q['arrival'] ? e(date('d M Y', strtotime($q['arrival']))) : '–' ?> · <?= e($q['travellers']) ?> pax</small></td>
                            <td><small><?= e(date('d M, H:i', strtotime($q['created_at']))) ?></small></td>
                            <td><?= $q['status'] === 'new' ? '<span class="badge badge-warning">New</span>' : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentEnquiries): ?><tr><td colspan="4" class="text-center text-muted py-4">No enquiries yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="<?= can('enquiries') ? 'col-lg-5' : 'col-lg-7' ?> mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Recently edited tours</h6>
                <a href="tours.php" class="small">All tours</a>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentTours as $t): ?>
                    <li class="list-group-item d-flex align-items-center">
                        <?php if ($t['image']): ?><img src="<?= e(img_url($t['image'], 'small')) ?>" class="thumb-sm mr-3" alt=""><?php else: ?><span class="thumb-sm mr-3 d-inline-block"></span><?php endif; ?>
                        <div class="flex-grow-1"><a href="tour-edit.php?id=<?= (int) $t['id'] ?>"><?= e($t['title']) ?></a><br><small class="text-muted"><?= e($t['category']) ?></small></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <?php if (!can('enquiries')): ?>
    <div class="col-lg-5 mb-4">
        <div class="card shadow">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Quick links</h6></div>
            <div class="list-group list-group-flush">
                <?php foreach ($PAGE_FIELDS as $key => $p): ?>
                    <a class="list-group-item list-group-item-action" href="pages.php?page=<?= e($key) ?>"><i class="fas fa-pen fa-fw text-gray-400 mr-2"></i><?= e($p['title']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
