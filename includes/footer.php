</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <h4><?= e(setting('site_name') . ' ' . setting('site_tagline')) ?></h4>
            <p><?= block_br('footer', 'about_text') ?></p>
        </div>
        <div>
            <h4>Explore</h4>
            <a href="tours.php">Tour Packages</a>
            <a href="destinations.php">Destinations</a>
            <a href="experiences.php">Experiences</a>
            <a href="about.php">About Us</a>
        </div>
        <div>
            <h4>Contact</h4>
            <p><i class="fa-solid fa-location-dot"></i> <?= e(setting('address')) ?></p>
            <p><i class="fa-solid fa-phone"></i> <a href="tel:<?= e(preg_replace('/\s+/', '', setting('phone'))) ?>"><?= e(setting('phone')) ?></a></p>
            <p><i class="fa-solid fa-envelope"></i> <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>
        </div>
        <div>
            <h4><?= e(block('footer', 'cta_title')) ?></h4>
            <p><?= block_br('footer', 'cta_text') ?></p>
            <a href="contact.php" class="btn btn-gold btn-sm">Request a Quote</a>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> <?= e(setting('site_name') . ' ' . setting('site_tagline')) ?>. All rights reserved.
    </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
