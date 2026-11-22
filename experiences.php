<?php
require __DIR__ . '/includes/config.php';
$currentPage = 'experiences';
$pageTitle = str_replace("\n", ' ', block('experiences', 'hero_title'));
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero split-hero">
    <div class="container split-hero-inner">
        <div>
            <h1><?= block_br('experiences', 'hero_title') ?></h1>
            <p><?= block_br('experiences', 'hero_subtitle') ?></p>
        </div>
        <div class="split-hero-photo photo" style="<?= bg(block('experiences', 'hero_image')) ?>"></div>
    </div>
</section>

<section class="container">
    <div class="grid-4">
        <?php foreach (get_experiences() as $x): ?>
            <a class="exp-card" href="contact.php?experience=<?= urlencode($x['name']) ?>">
                <i class="fa-solid <?= e($x['icon']) ?>"></i>
                <h3><?= e($x['name']) ?></h3>
                <p><?= e($x['description']) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
