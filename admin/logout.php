<?php
define('PUBLIC_ADMIN_PAGE', true);
require __DIR__ . '/_init.php'; // checks the CSRF token on POST

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION = [];
    session_regenerate_id(true);
    flash('success', 'You have been logged out.');
}
redirect('login.php');
