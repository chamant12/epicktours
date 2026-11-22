<?php
/** @var string $pageTitle  @var string $currentPage */
$siteName = setting('site_name');
$tagline = setting('site_tagline');
$logo = setting('logo');

// SEO: the page's own values from the CMS, falling back to automatic ones
$seoPage = $seoPage ?? ($currentPage ?? '');
$metaTitle = $seoPage !== '' ? block($seoPage, 'meta_title') : '';
$metaTitle = $metaTitle !== '' ? $metaTitle : ($pageTitle ?? $siteName) . ' | ' . $siteName . ' ' . $tagline;
$metaDescription = ($seoPage !== '' ? block($seoPage, 'meta_description') : '') ?: ($pageDescription ?? '') ?: setting('meta_description');
$metaKeywords = ($seoPage !== '' ? block($seoPage, 'meta_keywords') : '') ?: setting('meta_keywords');
$ogImage = $ogImage ?? (block('home', 'hero_image') ?: '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($metaTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <?php if ($metaKeywords !== ''): ?><meta name="keywords" content="<?= e($metaKeywords) ?>"><?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($metaTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <?php if ($ogImage && is_file(ROOT . '/' . $ogImage)): ?><meta property="og:image" content="<?= e(site_url() . $ogImage) ?>"><?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo" aria-label="<?= e($siteName) ?> home">
            <?php if ($logo && is_file(ROOT . '/' . $logo)): ?>
                <img src="<?= e(img_url($logo)) ?>" alt="<?= e($siteName) ?>">
            <?php else: ?>
                <svg class="logo-mark" viewBox="0 0 48 40" aria-hidden="true">
                    <path d="M4 38 C4 18 14 6 24 6 S44 18 44 38" fill="none" stroke="#c9971f" stroke-width="4"/>
                    <path d="M12 38 C12 24 18 15 24 15 S36 24 36 38" fill="none" stroke="#2f6b3a" stroke-width="3.5"/>
                    <path d="M19 38 C19 30 21 24 24 24 S29 30 29 38" fill="#c9971f"/>
                    <circle cx="24" cy="4" r="3" fill="#c9971f"/>
                </svg>
                <span class="logo-text">
                    <strong><?= e($siteName) ?></strong>
                    <small><?= e($tagline) ?></small>
                </span>
            <?php endif; ?>
        </a>

        <button class="nav-toggle" aria-label="Open menu" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>

        <nav class="main-nav">
            <a href="index.php"<?= nav_active('home') ?>>Home</a>
            <a href="tours.php"<?= nav_active('tours') ?>>Tours</a>
            <a href="destinations.php"<?= nav_active('destinations') ?>>Destinations</a>
            <a href="experiences.php"<?= nav_active('experiences') ?>>Experiences</a>
            <a href="about.php"<?= nav_active('about') ?>>About Us</a>
            <a href="contact.php"<?= nav_active('contact') ?>>Contact</a>
            <a href="contact.php" class="btn btn-gold btn-sm">Request a Quote</a>
        </nav>
    </div>
</header>

<main>
