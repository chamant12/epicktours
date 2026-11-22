<?php
require __DIR__ . '/_init.php';
require_can('enquiries');
$adminTitle = 'Enquiry';
$activeNav = 'enquiries';

$id = (int) ($_GET['id'] ?? 0);
$r = q('SELECT * FROM enquiries WHERE id = ?', [$id])->fetch();
if (!$r) {
    flash('danger', 'Enquiry not found.');
    redirect('enquiries.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = (string) ($_POST['status'] ?? '');
    if (in_array($new, ['new', 'read', 'replied'], true)) {
        q('UPDATE enquiries SET status = ? WHERE id = ?', [$new, $id]);
        flash('success', 'Status updated.');
    }
    redirect('enquiry.php?id=' . $id);
}

// Opening a new enquiry marks it as read
if ($r['status'] === 'new') {
    q("UPDATE enquiries SET status = 'read' WHERE id = ?", [$id]);
    $r['status'] = 'read';
}

$subject = rawurlencode('Your Sri Lanka tour enquiry – ' . setting('site_name'));
require __DIR__ . '/_header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Enquiry from <?= e($r['name']) ?></h1>
    <a href="enquiries.php" class="btn btn-sm btn-light">All enquiries</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Received</dt><dd class="col-sm-8"><?= e(date('l, d F Y \a\t H:i', strtotime($r['created_at']))) ?></dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></dd>
                    <dt class="col-sm-4">Country</dt><dd class="col-sm-8"><?= e($r['country']) ?></dd>
                    <dt class="col-sm-4">Arrival</dt><dd class="col-sm-8"><?= $r['arrival'] ? e(date('d M Y', strtotime($r['arrival']))) : '–' ?></dd>
                    <dt class="col-sm-4">Departure</dt><dd class="col-sm-8"><?= $r['departure'] ? e(date('d M Y', strtotime($r['departure']))) : '–' ?></dd>
                    <dt class="col-sm-4">Travellers</dt><dd class="col-sm-8"><?= e($r['travellers']) ?></dd>
                    <dt class="col-sm-4">Type of holiday</dt><dd class="col-sm-8"><?= e($r['holiday_type'] ?: '–') ?></dd>
                </dl>
                <hr>
                <h6 class="font-weight-bold">Message</h6>
                <p class="mb-0"><?= $r['message'] !== '' ? nl2br(e($r['message'])) : '<span class="text-muted">No message</span>' ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-body">
                <a href="mailto:<?= e($r['email']) ?>?subject=<?= e($subject) ?>" class="btn btn-primary btn-block mb-3"><i class="fas fa-reply"></i> Reply by email</a>
                <form method="post">
                    <?= csrf_field() ?>
                    <label class="small font-weight-bold">Status</label>
                    <div class="input-group">
                        <select name="status" class="form-control">
                            <?php foreach (['new' => 'New', 'read' => 'Read', 'replied' => 'Replied'] as $k => $label): ?>
                                <option value="<?= $k ?>"<?= $r['status'] === $k ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="input-group-append"><button class="btn btn-outline-primary">Update</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
