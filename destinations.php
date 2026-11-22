<?php
require __DIR__ . '/includes/config.php';
$currentPage = 'destinations';
$pageTitle = block('destinations', 'hero_title');
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container page-hero-inner">
        <div>
            <h1><?= e(block('destinations', 'hero_title')) ?></h1>
            <p><?= block_br('destinations', 'hero_subtitle') ?></p>
        </div>
        <i class="fa-solid fa-tower-observation page-hero-icon" aria-hidden="true"></i>
    </div>
</section>

<section class="container">
    <div class="grid-3">
        <?php foreach (get_destinations() as $slug => $d): ?>
            <a class="overlay-card dest-card" href="tours.php?dest=<?= e($slug) ?>" style="<?= bg($d['image']) ?>">
                <div class="overlay-text">
                    <h3 class="script"><?= e($d['name']) ?></h3>
                    <p class="dest-text"><?= e($d['description']) ?></p>
                </div>
                <span class="arrow-btn"><i class="fa-solid fa-arrow-right"></i></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
