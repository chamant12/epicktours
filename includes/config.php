<?php
/**
 * Server settings. Everything else (brand, contact details, page text,
 * images) is edited in the admin CMS at /admin.
 *
 * To keep real credentials out of git, copy these lines into
 * includes/config.local.php and change them there – that file is loaded
 * instead when it exists.
 */

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

defined('DB_HOST') || define('DB_HOST', '127.0.0.1');
defined('DB_PORT') || define('DB_PORT', '3306');
defined('DB_NAME') || define('DB_NAME', 'epicktours');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');

// Largest image a CMS user may upload (bytes). PHP's upload_max_filesize and
// post_max_size must be at least this big too.
defined('MAX_UPLOAD_BYTES') || define('MAX_UPLOAD_BYTES', 15 * 1024 * 1024);

// Quality used when saving optimised images (0–100)
defined('IMAGE_QUALITY') || define('IMAGE_QUALITY', 80);

define('ROOT', dirname(__DIR__));

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/cms-fields.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/images.php';
require_once __DIR__ . '/mailer.php';
