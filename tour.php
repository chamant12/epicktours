<?php
require __DIR__ . '/includes/config.php';
$currentPage = 'tours';

$tour = find_tour((string) ($_GET['slug'] ?? ''));
if (!$tour) {
    http_response_code(404);
    $pageTitle = 'Tour not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="container empty"><h1>Tour not found</h1><p><a href="tours.php">Browse all tours</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $tour['title'];
$pageDescription = $tour['summary'];
$seoPage = 'tour-' . $tour['id']; // SEO fields edited on the tour's admin page
$ogImage = $tour['image'];
$gallery = tour_gallery($tour);
$allDestinations = get_destinations(false);
$destNames = array_values(array_filter(array_map(fn ($d) => $allDestinations[$d]['name'] ?? null, $tour['destinations'])));
$mapQuery = urlencode(($destNames ? implode(', ', $destNames) . ', ' : '') . 'Sri Lanka');
$quoteUrl = 'contact.php?tour=' . urlencode($tour['slug']);
require __DIR__ . '/includes/header.php';
?>

<section class="container tour-detail">
    <nav class="breadcrumb">
        <a href="index.php">Home</a> <i class="fa-solid fa-chevron-right"></i>
        <a href="tours.php">Tours</a> <i class="fa-solid fa-chevron-right"></i>
        <span><?= e($tour['title']) ?></span>
    </nav>

    <h1 class="detail-title"><?= e($tour['title']) ?></h1>
    <div class="detail-meta">
        <span><i class="fa-regular fa-calendar"></i> <?= (int) $tour['days'] ?> Days / <?= (int) $tour['nights'] ?> Nights</span>
        <span><i class="fa-solid fa-users"></i> From <?= e(price($tour['price'])) ?> per person</span>
    </div>

    <div class="detail-layout">
        <div class="detail-main">
            <div class="gallery-main photo" id="galleryMain" style="<?= bg($gallery[0]) ?>"></div>
            <?php if (count($gallery) > 1): ?>
            <div class="gallery-strip">
                <button class="strip-nav" data-dir="-1" aria-label="Previous photos"><i class="fa-solid fa-chevron-left"></i></button>
                <div class="strip-track" id="stripTrack">
                    <?php foreach ($gallery as $i => $img): ?>
                        <button class="thumb photo<?= $i === 0 ? ' active' : '' ?>" style="<?= bg($img, 'small') ?>" data-style="<?= bg($img) ?>" aria-label="Photo <?= $i + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
                <button class="strip-nav" data-dir="1" aria-label="Next photos"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <?php endif; ?>

            <p class="summary"><?= e($tour['summary']) ?></p>

            <div class="tabs" role="tablist">
                <button class="tab active" data-tab="itinerary">Itinerary</button>
                <button class="tab" data-tab="inclusions">Inclusions</button>
                <button class="tab" data-tab="exclusions">Exclusions</button>
                <button class="tab" data-tab="hotels">Hotels</button>
                <button class="tab" data-tab="map">Map</button>
            </div>

            <div class="tab-panel active" id="tab-itinerary">
                <h2 class="print-only">Itinerary</h2>
                <ol class="timeline">
                    <?php foreach ($tour['itinerary'] as $i => $day): ?>
                        <li>
                            <span class="day">Day <?= $i + 1 ?></span>
                            <div><strong><?= e($day[0] ?? '') ?></strong><p><?= e($day[1] ?? '') ?></p></div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div class="tab-panel" id="tab-inclusions">
                <h2 class="print-only">Inclusions</h2>
                <ul class="check-list"><?php foreach ($tour['inclusions'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
            </div>
            <div class="tab-panel" id="tab-exclusions">
                <h2 class="print-only">Exclusions</h2>
                <ul class="cross-list"><?php foreach ($tour['exclusions'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
            </div>
            <div class="tab-panel" id="tab-hotels">
                <h2 class="print-only">Hotels</h2>
                <table class="hotel-table">
                    <thead><tr><th>Location</th><th>Hotel</th></tr></thead>
                    <tbody><?php foreach ($tour['hotels'] as $h): ?><tr><td><?= e($h[0] ?? '') ?></td><td><?= e($h[1] ?? '') ?></td></tr><?php endforeach; ?></tbody>
                </table>
            </div>
            <div class="tab-panel no-print" id="tab-map">
                <iframe class="map-frame" loading="lazy" title="Tour map" src="https://maps.google.com/maps?q=<?= $mapQuery ?>&amp;z=7&amp;output=embed"></iframe>
            </div>
        </div>

        <aside class="detail-side">
            <div class="price-box">
                <small>From</small>
                <div class="big-price"><?= e(price($tour['price'])) ?></div>
                <small>per person</small>
                <a href="<?= e($quoteUrl) ?>" class="btn btn-gold btn-block">Request a Quote</a>
                <button type="button" class="btn btn-outline btn-block" onclick="window.print()"><i class="fa-solid fa-download"></i> Download Itinerary</button>
            </div>
            <?php if ($tour['highlights']): ?>
            <div class="highlights">
                <h3>Tour Highlights</h3>
                <ul class="check-list"><?php foreach ($tour['highlights'] as $h): ?><li><?= e($h) ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
