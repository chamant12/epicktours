<?php
/**
 * Loaded at the top of every admin page: sets up the site, checks the user
 * is logged in (unless the page sets PUBLIC_ADMIN_PAGE) and checks the CSRF
 * token on every form post.
 */
define('IN_ADMIN', true);
require dirname(__DIR__) . '/includes/config.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

/**
 * What each role may do. Admins can do everything; content writers edit
 * website content only.
 */
const ROLE_PERMISSIONS = [
    'admin'          => ['content', 'enquiries', 'users', 'settings'],
    'content_writer' => ['content'],
];

/** The logged-in, active user (with role slug and name), or null. */
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $user = q('SELECT u.*, r.slug AS role, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND u.is_active = 1', [$_SESSION['uid']])->fetch() ?: null;
            if (!$user) {
                unset($_SESSION['uid']);
            }
        }
    }
    return $user;
}

function can(string $permission): bool
{
    $user = current_user();
    return $user && in_array($permission, ROLE_PERMISSIONS[$user['role']] ?? [], true);
}

function require_can(string $permission): void
{
    if (!can($permission)) {
        http_response_code(403);
        $adminTitle = 'Access denied';
        require __DIR__ . '/_header.php';
        echo '<div class="alert alert-warning">Your role does not have access to this page.</div>';
        require __DIR__ . '/_footer.php';
        exit;
    }
}

/** Normalise a multi-file input ($_FILES['x'] with name[] etc.) to a list keyed like the input. */
function files_list(string $key): array
{
    $out = [];
    if (empty($_FILES[$key]['name']) || !is_array($_FILES[$key]['name'])) {
        return $out;
    }
    foreach ($_FILES[$key]['name'] as $i => $name) {
        if ($_FILES[$key]['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[$i] = [
            'name' => $name,
            'tmp_name' => $_FILES[$key]['tmp_name'][$i],
            'error' => $_FILES[$key]['error'][$i],
            'size' => $_FILES[$key]['size'][$i],
        ];
    }
    return $out;
}

if (!defined('PUBLIC_ADMIN_PAGE') && !current_user()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valid($_POST['csrf'] ?? null)) {
    // An empty POST usually means the upload was bigger than post_max_size
    flash('danger', empty($_POST) ? 'Nothing was saved – the upload was too large for the server.' : 'Your session expired. Please try again.');
    redirect($_SERVER['REQUEST_URI']);
}

require_once __DIR__ . '/_form.php';
