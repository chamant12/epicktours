<?php
/**
 * Images: shows every image the website uses, whether it is optimised for
 * the web, what the server supports, and re-optimises old images in one go.
 */
require __DIR__ . '/_init.php';
require_can('settings');
$adminTitle = 'Images';
$activeNav = 'images';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'optimise_all') {
    @set_time_limit(0);
    $done = 0;
    $failed = [];
    $savedBytes = 0;
    foreach (image_inventory() as $item) {
        if (!$item['exists'] || !$item['problems']) {
            continue;
        }
        try {
            $new = optimise_inventory_item($item);
            $savedBytes += max(0, $item['bytes'] - (int) filesize(ROOT . '/' . $new));
            $done++;
        } catch (Throwable $ex) {
            $failed[] = $item['where'] . ': ' . $ex->getMessage();
            error_log('Image optimise failed for ' . $item['path'] . ': ' . $ex->getMessage());
        }
    }
    if ($done) {
        flash('success', "Optimised {$done} image(s), saving " . round($savedBytes / 1024) . ' KB.');
    } elseif (!$failed) {
        flash('success', 'All images are already optimised.');
    }
    foreach ($failed as $msg) {
        flash('danger', $msg);
    }
    redirect('images.php');
}

$items = image_inventory();
$todo = count(array_filter($items, fn ($i) => $i['exists'] && $i['problems']));
$missing = count(array_filter($items, fn ($i) => !$i['exists']));
$totalKb = round(array_sum(array_column($items, 'bytes')) / 1024);

$gd = extension_loaded('gd');
$checks = [
    ['GD image library', $gd, $gd ? 'Installed' : 'Missing – images cannot be resized or compressed. Ask your host to enable the PHP "gd" extension.'],
    ['WebP support', webp_supported(), webp_supported() ? 'Images are saved as WebP (smallest files)' : 'Not available – images are saved as JPEG/PNG instead. Ask your host for GD with WebP.'],
    ['Read WebP uploads', function_exists('imagecreatefromwebp'), function_exists('imagecreatefromwebp') ? 'Yes' : 'No – WebP files cannot be uploaded'],
    ['Phone photo rotation (EXIF)', function_exists('exif_read_data'), function_exists('exif_read_data') ? 'Yes' : 'Not available – some phone photos may appear sideways'],
    ['Uploads folder writable', is_writable(ROOT . '/uploads'), is_writable(ROOT . '/uploads') ? 'Yes' : 'No – the web server cannot save images in /uploads'],
];
$limits = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
];

require __DIR__ . '/_header.php';
?>
<h1 class="h3 mb-2 text-gray-800">Images</h1>
<p class="mb-4 text-muted">Every image uploaded in the CMS is resized to the space it is shown in, compressed (WebP, quality <?= (int) IMAGE_QUALITY ?>, lowered automatically for heavy photos) and has its metadata removed. Images added another way – for example by FTP or before this was set up – are listed below and can be optimised with one click.</p>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Server image support</h6></div>
            <div class="card-body">
                <table class="table table-sm mb-3">
                    <?php foreach ($checks as [$label, $ok, $note]): ?>
                        <tr>
                            <td style="width:40%"><?= e($label) ?></td>
                            <td><span class="badge badge-<?= $ok ? 'success' : 'danger' ?>"><?= $ok ? 'OK' : 'Problem' ?></span> <small class="text-muted"><?= e($note) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <small class="text-muted">
                    PHP limits: upload_max_filesize <?= e($limits['upload_max_filesize']) ?>,
                    post_max_size <?= e($limits['post_max_size']) ?>,
                    memory_limit <?= e($limits['memory_limit']) ?>.
                    The CMS accepts images up to <?= round(MAX_UPLOAD_BYTES / 1048576) ?> MB – the first two limits must be at least that big.
                </small>
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Summary</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong><?= count($items) ?></strong> images in use, <strong><?= $totalKb ?> KB</strong> in total.</p>
                <p class="mb-1"><strong class="<?= $todo ? 'text-warning' : 'text-success' ?>"><?= $todo ?></strong> need optimising.</p>
                <?php if ($missing): ?><p class="mb-1"><strong class="text-danger"><?= $missing ?></strong> missing or external – re-upload them on their edit page.</p><?php endif; ?>
                <form method="post" class="mt-3" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = 'Optimising… please wait';">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="optimise_all">
                    <button class="btn btn-primary" <?= ($todo && $gd) ? '' : 'disabled' ?>><i class="fas fa-wand-magic-sparkles"></i> Optimise all (<?= $todo ?>)</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-5">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th></th><th>Used on</th><th>Size</th><th>Format</th><th>File</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($items as $i): ?>
                    <tr>
                        <td><?php if ($i['exists']): ?><img src="<?= e(img_url($i['path'], 'small')) ?>" class="thumb-sm" alt="" loading="lazy"><?php else: ?><span class="thumb-sm d-inline-block"></span><?php endif; ?></td>
                        <td><a href="<?= e($i['edit']) ?>"><?= e($i['where']) ?></a><br><small class="text-muted"><?= e($i['path']) ?></small></td>
                        <td><?= $i['width'] ? (int) $i['width'] . '×' . (int) $i['height'] : '–' ?></td>
                        <td><?= e($i['format'] ?: '–') ?></td>
                        <td><?= $i['bytes'] ? round($i['bytes'] / 1024) . ' KB' : '–' ?></td>
                        <td>
                            <?php if (!$i['problems']): ?>
                                <span class="badge badge-success">Optimised</span>
                            <?php else: ?>
                                <?php foreach ($i['problems'] as $p): ?><span class="badge badge-<?= $i['exists'] ? 'warning' : 'danger' ?> mr-1"><?= e($p) ?></span><?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$items): ?><tr><td colspan="6" class="text-center text-muted py-4">No images uploaded yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
