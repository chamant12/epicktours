<?php /** @var array $tour */ ?>
<a class="tour-card" href="tour.php?slug=<?= e($tour['slug']) ?>">
    <div class="photo" style="<?= bg($tour['image'], 'small') ?>"></div>
    <div class="tour-card-body">
        <h3><?= e($tour['title']) ?></h3>
        <p class="muted"><?= (int) $tour['days'] ?> Days / <?= (int) $tour['nights'] ?> Nights</p>
        <div class="tour-card-foot">
            <strong>From <?= e(price($tour['price'])) ?></strong>
            <span class="arrow-btn dark"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
    </div>
</a>
