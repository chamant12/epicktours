<?php
require __DIR__ . '/_init.php';
require_can('content');
$activeNav = 'tours';

$id = (int) ($_GET['id'] ?? 0);
$tour = $id ? q('SELECT * FROM tours WHERE id = ?', [$id])->fetch() : null;
if ($id && !$tour) {
    flash('danger', 'Tour not found.');
    redirect('tours.php');
}
$tour = $tour ? decode_tour($tour) : [
    'id' => 0, 'slug' => '', 'title' => '', 'category' => '', 'days' => 7, 'nights' => 6, 'price' => 0,
    'summary' => '', 'image' => '', 'highlights' => [], 'itinerary' => [], 'inclusions' => [], 'exclusions' => [],
    'hotels' => [], 'destinations' => [], 'is_featured' => 0, 'is_active' => 1, 'sort_order' => 0,
];
$categories = tour_categories();
$allDestinations = get_destinations(false);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = [
        'title'        => trim((string) ($_POST['title'] ?? '')),
        'slug'         => slugify(trim((string) ($_POST['slug'] ?? '')) ?: (string) ($_POST['title'] ?? '')),
        'category'     => (string) ($_POST['category'] ?? ''),
        'days'         => max(1, (int) ($_POST['days'] ?? 1)),
        'nights'       => max(0, (int) ($_POST['nights'] ?? 0)),
        'price'        => max(0, (int) ($_POST['price'] ?? 0)),
        'summary'      => trim((string) ($_POST['summary'] ?? '')),
        'highlights'   => lines((string) ($_POST['highlights'] ?? '')),
        'itinerary'    => pair_lines((string) ($_POST['itinerary'] ?? '')),
        'inclusions'   => lines((string) ($_POST['inclusions'] ?? '')),
        'exclusions'   => lines((string) ($_POST['exclusions'] ?? '')),
        'hotels'       => pair_lines((string) ($_POST['hotels'] ?? '')),
        'destinations' => array_values(array_intersect((array) ($_POST['destinations'] ?? []), array_keys($allDestinations))),
        'is_featured'  => !empty($_POST['is_featured']) ? 1 : 0,
        'is_active'    => !empty($_POST['is_active']) ? 1 : 0,
        'sort_order'   => (int) ($_POST['sort_order'] ?? 0),
    ];

    if ($in['title'] === '') {
        $errors[] = 'Please enter a title.';
    }
    if (!in_array($in['category'], $categories, true)) {
        $errors[] = 'Please choose a category.';
    }
    if (q('SELECT id FROM tours WHERE slug = ? AND id <> ?', [$in['slug'], $tour['id']])->fetch()) {
        $errors[] = 'Another tour already uses the web address "' . $in['slug'] . '".';
    }

    if (!$errors) {
        try {
            $in['image'] = handle_image_field('image', $tour['image'], 'tour');
        } catch (RuntimeException $ex) {
            $in['image'] = $tour['image'];
            $errors[] = 'Main image: ' . $ex->getMessage();
        }

        $params = [
            $in['slug'], $in['title'], $in['category'], $in['days'], $in['nights'], $in['price'], $in['summary'], $in['image'],
            json_encode($in['highlights']), json_encode($in['itinerary']), json_encode($in['inclusions']),
            json_encode($in['exclusions']), json_encode($in['hotels']), json_encode($in['destinations']),
            $in['is_featured'], $in['is_active'], $in['sort_order'],
        ];
        $cols = 'slug = ?, title = ?, category = ?, days = ?, nights = ?, price = ?, summary = ?, image = ?, highlights = ?, itinerary = ?, inclusions = ?, exclusions = ?, hotels = ?, destinations = ?, is_featured = ?, is_active = ?, sort_order = ?';
        if ($tour['id']) {
            q("UPDATE tours SET {$cols}, updated_at = NOW() WHERE id = ?", array_merge($params, [$tour['id']]));
            $tourId = (int) $tour['id'];
        } else {
            q("INSERT INTO tours SET {$cols}, updated_at = NOW()", $params);
            $tourId = (int) db()->lastInsertId();
        }

        // Gallery: delete ticked photos
        foreach ((array) ($_POST['gallery_delete'] ?? []) as $imgId) {
            $p = q('SELECT path FROM tour_images WHERE id = ? AND tour_id = ?', [(int) $imgId, $tourId])->fetchColumn();
            if ($p !== false) {
                delete_image($p);
                q('DELETE FROM tour_images WHERE id = ?', [(int) $imgId]);
            }
        }
        // Gallery: replace individual photos
        foreach (files_list('gallery_replace') as $imgId => $file) {
            $old = q('SELECT path FROM tour_images WHERE id = ? AND tour_id = ?', [(int) $imgId, $tourId])->fetchColumn();
            if ($old === false) {
                continue;
            }
            try {
                $new = save_uploaded_image($file, 'tour');
                q('UPDATE tour_images SET path = ? WHERE id = ?', [$new, (int) $imgId]);
                delete_image($old);
            } catch (RuntimeException $ex) {
                $errors[] = 'Photo "' . $file['name'] . '": ' . $ex->getMessage();
            }
        }
        // Gallery: new photos
        $next = (int) q('SELECT COALESCE(MAX(sort_order), 0) FROM tour_images WHERE tour_id = ?', [$tourId])->fetchColumn();
        foreach (files_list('gallery_new') as $file) {
            try {
                q('INSERT INTO tour_images (tour_id, path, sort_order) VALUES (?, ?, ?)', [$tourId, save_uploaded_image($file, 'tour'), ++$next]);
            } catch (RuntimeException $ex) {
                $errors[] = 'Photo "' . $file['name'] . '": ' . $ex->getMessage();
            }
        }
        // Gallery: order
        foreach ((array) ($_POST['gallery_order'] ?? []) as $imgId => $order) {
            q('UPDATE tour_images SET sort_order = ? WHERE id = ? AND tour_id = ?', [(int) $order, (int) $imgId, $tourId]);
        }
        // SEO fields live in page_blocks under "tour-<id>"
        foreach ($SEO_FIELDS as $key => $f) {
            q('INSERT INTO page_blocks (page, bkey, value, updated_by, updated_at) VALUES (?, ?, ?, ?, NOW())
               ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by = VALUES(updated_by), updated_at = NOW()',
              ['tour-' . $tourId, $key, trim((string) ($_POST['seo'][$key] ?? '')), current_user()['id']]);
        }

        foreach ($errors as $err) {
            flash('danger', $err);
        }
        flash('success', $errors ? 'Tour saved, but some images could not be processed.' : 'Tour saved.');
        redirect('tour-edit.php?id=' . $tourId);
    }

    // Validation failed: show what was typed
    $tour = array_merge($tour, $in);
}

$gallery = $tour['id'] ? q('SELECT * FROM tour_images WHERE tour_id = ? ORDER BY sort_order, id', [$tour['id']])->fetchAll() : [];
$seo = $tour['id'] ? q('SELECT bkey, value FROM page_blocks WHERE page = ?', ['tour-' . $tour['id']])->fetchAll(PDO::FETCH_KEY_PAIR) : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seo = array_map('strval', (array) ($_POST['seo'] ?? [])); // keep what was typed
}
$adminTitle = $tour['id'] ? 'Edit tour' : 'New tour';
require __DIR__ . '/_header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= $tour['id'] ? 'Edit: ' . e($tour['title']) : 'New tour' ?></h1>
    <div>
        <?php if ($tour['id']): ?><a href="../tour.php?slug=<?= e($tour['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> View</a><?php endif; ?>
        <a href="tours.php" class="btn btn-sm btn-light">All tours</a>
    </div>
</div>

<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4 section-card">
                <div class="card-header py-3"><h6 class="font-weight-bold text-primary">Details</h6></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Title *</label>
                        <input class="form-control" name="title" value="<?= e($tour['title']) ?>" required data-slug-source>
                    </div>
                    <div class="form-group">
                        <label>Web address</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">tour.php?slug=</span></div>
                            <input class="form-control" name="slug" value="<?= e($tour['slug']) ?>" data-slug-target>
                        </div>
                        <small class="form-text text-muted">Filled in from the title if left empty.</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Category *</label>
                            <select class="form-control" name="category" required>
                                <option value="">Choose…</option>
                                <?php foreach ($categories as $c): ?><option<?= $tour['category'] === $c ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-2"><label>Days</label><input type="number" min="1" class="form-control" name="days" value="<?= (int) $tour['days'] ?>"></div>
                        <div class="form-group col-md-2"><label>Nights</label><input type="number" min="0" class="form-control" name="nights" value="<?= (int) $tour['nights'] ?>"></div>
                        <div class="form-group col-md-4">
                            <label>Price from (per person)</label>
                            <div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><?= e(setting('currency')) ?></span></div><input type="number" min="0" class="form-control" name="price" value="<?= (int) $tour['price'] ?>"></div>
                        </div>
                    </div>
                    <div class="form-group"><label>Short summary</label><textarea class="form-control" name="summary" rows="2"><?= e($tour['summary']) ?></textarea></div>
                    <div class="form-group"><label>Tour highlights <small class="text-muted">(one per line)</small></label><textarea class="form-control" name="highlights" rows="5"><?= e(implode("\n", $tour['highlights'])) ?></textarea></div>
                </div>
            </div>

            <div class="card shadow mb-4 section-card">
                <div class="card-header py-3"><h6 class="font-weight-bold text-primary">Itinerary, inclusions & hotels</h6></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Itinerary <small class="text-muted">– one day per line: <code>Title | What happens that day</code></small></label>
                        <textarea class="form-control mono" name="itinerary" rows="10"><?= e(pairs_to_text($tour['itinerary'])) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label>Inclusions <small class="text-muted">(one per line)</small></label><textarea class="form-control" name="inclusions" rows="6"><?= e(implode("\n", $tour['inclusions'])) ?></textarea></div>
                        <div class="form-group col-md-6"><label>Exclusions <small class="text-muted">(one per line)</small></label><textarea class="form-control" name="exclusions" rows="6"><?= e(implode("\n", $tour['exclusions'])) ?></textarea></div>
                    </div>
                    <div class="form-group">
                        <label>Hotels <small class="text-muted">– one per line: <code>Location | Hotel name</code></small></label>
                        <textarea class="form-control mono" name="hotels" rows="5"><?= e(pairs_to_text($tour['hotels'])) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4 section-card">
                <div class="card-header py-3"><h6 class="font-weight-bold text-primary">Photo gallery</h6></div>
                <div class="card-body">
                    <p class="small text-muted">The main image (right) is shown first, followed by these photos. Every photo is resized to <?= e(spec_label('tour')) ?> with a smaller copy for thumbnails, and optimised automatically.</p>
                    <?php if ($gallery): ?>
                        <div class="gallery-admin mb-3">
                            <?php foreach ($gallery as $g): ?>
                                <div class="card">
                                    <img src="<?= e(img_url($g['path'], 'small')) ?>" class="card-img-top" alt="">
                                    <div class="card-body p-2">
                                        <div class="custom-file custom-file-sm mb-2">
                                            <input type="file" class="custom-file-input" id="rep<?= (int) $g['id'] ?>" name="gallery_replace[<?= (int) $g['id'] ?>]" accept="image/jpeg,image/png,image/webp,image/gif">
                                            <label class="custom-file-label" for="rep<?= (int) $g['id'] ?>">Replace…</label>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="small mb-0">Order <input type="number" name="gallery_order[<?= (int) $g['id'] ?>]" value="<?= (int) $g['sort_order'] ?>" class="form-control form-control-sm d-inline-block" style="width:64px"></label>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="del<?= (int) $g['id'] ?>" name="gallery_delete[]" value="<?= (int) $g['id'] ?>">
                                                <label class="custom-control-label small text-danger" for="del<?= (int) $g['id'] ?>">Delete</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <label>Add photos</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="gallery_new" name="gallery_new[]" multiple accept="image/jpeg,image/png,image/webp,image/gif">
                        <label class="custom-file-label" for="gallery_new">Choose one or more photos…</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4 section-card">
                <div class="card-header py-3"><h6 class="font-weight-bold text-primary">Publish</h6></div>
                <div class="card-body">
                    <?= switch_field('is_active', 'Show on website', (bool) $tour['is_active']) ?>
                    <?= switch_field('is_featured', 'Feature on home page', (bool) $tour['is_featured']) ?>
                    <div class="form-group"><label>Display order</label><input type="number" class="form-control" name="sort_order" value="<?= (int) $tour['sort_order'] ?>"><small class="form-text text-muted">Lower numbers are shown first.</small></div>
                    <button class="btn btn-primary btn-block"><i class="fas fa-save"></i> Save tour</button>
                </div>
            </div>
            <div class="card shadow mb-4 section-card">
                <div class="card-header py-3"><h6 class="font-weight-bold text-primary">Main image</h6></div>
                <div class="card-body"><?= image_field('image', 'Card & header image', $tour['image'], 'tour') ?></div>
            </div>
            <div class="card shadow mb-4 section-card">
                <div class="card-header py-3"><h6 class="font-weight-bold text-primary">Destinations visited</h6></div>
                <div class="card-body">
                    <?php foreach ($allDestinations as $slug => $d): ?>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="d_<?= e($slug) ?>" name="destinations[]" value="<?= e($slug) ?>"<?= in_array($slug, $tour['destinations'], true) ? ' checked' : '' ?>>
                            <label class="custom-control-label" for="d_<?= e($slug) ?>"><?= e($d['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                    <small class="form-text text-muted">Used for the "tours that visit…" links on the Destinations page and the map.</small>
                </div>
            </div>
            <div class="card shadow mb-4 section-card">
                <div class="card-header py-3"><h6 class="font-weight-bold text-primary">Search engine (SEO)</h6></div>
                <div class="card-body">
                    <?php foreach ($SEO_FIELDS as $key => $f): ?>
                        <?= cms_field('seo[' . $key . ']', $f, (string) ($seo[$key] ?? '')) ?>
                    <?php endforeach; ?>
                    <small class="text-muted">If empty, the tour title and summary are used.</small>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/_footer.php'; ?>
