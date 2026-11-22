<?php
require __DIR__ . '/includes/config.php';
$currentPage = 'about';
$pageTitle = block('about', 'hero_title');
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container page-hero-inner">
        <div>
            <h1><?= e(block('about', 'hero_title')) ?></h1>
            <p><?= block_br('about', 'hero_subtitle') ?></p>
        </div>
        <i class="fa-solid fa-earth-asia page-hero-icon" aria-hidden="true"></i>
    </div>
</section>

<section class="container about">
    <div class="about-grid">
        <div>
            <h2 class="section-title"><?= e(block('about', 'story_title')) ?></h2>
            <?= paragraphs(block('about', 'story_body')) ?>
            <a href="contact.php" class="btn btn-gold"><?= e(block('about', 'story_btn')) ?></a>
        </div>
        <div class="photo about-photo" style="<?= bg(block('about', 'story_image')) ?>"></div>
    </div>

    <div class="grid-4 stats">
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <div><strong><?= e(block('about', "stat{$i}_value")) ?></strong><span><?= e(block('about', "stat{$i}_label")) ?></span></div>
        <?php endfor; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
