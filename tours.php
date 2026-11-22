<?php
require __DIR__ . '/includes/config.php';
$currentPage = 'tours';
$pageTitle = block('tours', 'hero_title');

$categories = tour_categories();
$destinations = get_destinations();
$cat  = $_GET['cat'] ?? '';
$dest = $_GET['dest'] ?? '';
if (!in_array($cat, $categories, true)) {
    $cat = '';
}
if (!isset($destinations[$dest])) {
    $dest = '';
}
$tours = get_tours(['category' => $cat, 'destination' => $dest]);

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container page-hero-inner">
        <div>
            <h1><?= e(block('tours', 'hero_title')) ?></h1>
            <p><?= block_br('tours', 'hero_subtitle') ?></p>
        </div>
        <i class="fa-solid fa-person-hiking page-hero-icon" aria-hidden="true"></i>
    </div>
</section>

<section class="container">
    <div class="filter-bar">
        <a href="tours.php" class="chip<?= $cat === '' ? ' active' : '' ?>">All Tours</a>
        <?php foreach ($categories as $c): ?>
            <a href="tours.php?cat=<?= urlencode($c) ?>" class="chip<?= $cat === $c ? ' active' : '' ?>"><?= e($c) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($dest): ?>
        <p class="filter-note">Showing tours that visit <strong><?= e($destinations[$dest]['name']) ?></strong>. <a href="tours.php">Clear filter</a></p>
    <?php endif; ?>

    <?php if ($tours): ?>
        <div class="grid-3 tour-grid">
            <?php foreach ($tours as $tour) { include __DIR__ . '/includes/tour-card.php'; } ?>
        </div>
    <?php else: ?>
        <p class="empty"><?= e(block('tours', 'empty_text')) ?> <a href="contact.php">Ask us for a custom tour</a>.</p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
