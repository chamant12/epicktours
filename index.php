<?php
require __DIR__ . '/includes/config.php';
$currentPage = 'home';
$pageTitle = block('home', 'hero_line1') . ' ' . block('home', 'hero_line2');
$featured = get_tours(['featured' => true]);
require __DIR__ . '/includes/header.php';
?>

<section class="hero" style="<?= bg(block('home', 'hero_image')) ?>">
    <div class="container hero-inner">
        <div class="hero-copy">
            <h1 class="script"><?= e(block('home', 'hero_line1')) ?><br><span><?= e(block('home', 'hero_line2')) ?></span></h1>
            <p class="lead"><?= block_br('home', 'hero_lead') ?></p>
            <div class="btn-row">
                <a href="tours.php" class="btn btn-gold"><?= e(block('home', 'hero_btn1')) ?> <i class="fa-solid fa-arrow-right"></i></a>
                <a href="contact.php" class="btn btn-outline"><?= e(block('home', 'hero_btn2')) ?></a>
            </div>
        </div>
    </div>
</section>

<section class="container themes">
    <?php foreach (get_themes() as $theme): ?>
        <a class="theme" href="tours.php<?= $theme['link_category'] !== '' ? '?cat=' . urlencode($theme['link_category']) : '' ?>">
            <i class="fa-solid <?= e($theme['icon']) ?>"></i>
            <span><?= e($theme['name']) ?></span>
        </a>
    <?php endforeach; ?>
</section>

<?php if ($featured): ?>
<section class="featured">
    <svg class="wave" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true"><path d="M0 40 C240 90 480 0 720 30 S1200 80 1440 20 V80 H0Z"/></svg>
    <div class="container">
        <h2 class="section-title light"><?= e(block('home', 'featured_title')) ?></h2>
        <div class="grid-3">
            <?php foreach ($featured as $tour): ?>
                <a class="overlay-card" href="tour.php?slug=<?= e($tour['slug']) ?>" style="<?= bg($tour['image'], 'small') ?>">
                    <div class="overlay-text">
                        <h3><?= e($tour['title']) ?></h3>
                        <small><?= (int) $tour['days'] ?> Days / <?= (int) $tour['nights'] ?> Nights</small>
                    </div>
                    <span class="arrow-btn"><i class="fa-solid fa-arrow-right"></i></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="center"><a href="tours.php" class="btn btn-gold"><?= e(block('home', 'featured_btn')) ?></a></div>
    </div>
</section>
<?php endif; ?>

<section class="container why">
    <h2 class="section-title"><?= e(block('home', 'why_title')) ?></h2>
    <div class="grid-4">
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="why-item">
                <i class="fa-solid <?= e(block('home', "why{$i}_icon")) ?>"></i>
                <h3><?= e(block('home', "why{$i}_title")) ?></h3>
                <p><?= block_br('home', "why{$i}_text") ?></p>
            </div>
        <?php endfor; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
