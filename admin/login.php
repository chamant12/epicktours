<?php
define('PUBLIC_ADMIN_PAGE', true);
require __DIR__ . '/_init.php';

if (current_user()) {
    redirect('index.php');
}

const MAX_FAILED_LOGINS = 5;
const LOCK_MINUTES = 15;

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $user = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();

    if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $error = 'Too many failed attempts. Please try again in ' . LOCK_MINUTES . ' minutes.';
    } elseif ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        q('UPDATE users SET failed_logins = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?', [$user['id']]);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $user['id'];
        unset($_SESSION['csrf']);
        redirect('index.php');
    } else {
        if ($user) {
            $fails = $user['failed_logins'] + 1;
            $lock = $fails >= MAX_FAILED_LOGINS ? date('Y-m-d H:i:s', time() + LOCK_MINUTES * 60) : null;
            q('UPDATE users SET failed_logins = ?, locked_until = ? WHERE id = ?', [$lock ? 0 : $fails, $lock, $user['id']]);
        }
        usleep(400000); // slow down guessing
        $error = $user && !$user['is_active'] ? 'This account has been deactivated.' : 'Incorrect email or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    <title>Login – <?= e(setting('site_name')) ?> Admin</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/vendor/sb-admin-2.min.css">
</head>
<body class="bg-gradient-primary">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-5 col-lg-6 col-md-8">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-mountain-sun fa-2x text-primary mb-2"></i>
                        <h1 class="h4 text-gray-900"><?= e(setting('site_name')) ?> Admin</h1>
                        <p class="small text-muted mb-0">Sign in to manage the website</p>
                    </div>
                    <?php foreach (take_flashes() as [$type, $msg]): ?>
                        <div class="alert alert-<?= e($type) ?>"><?= e($msg) ?></div>
                    <?php endforeach; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                    <form class="user" method="post" action="login.php">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <input type="email" name="email" class="form-control form-control-user" placeholder="Email address" value="<?= e($email) ?>" required autofocus autocomplete="username">
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" class="form-control form-control-user" placeholder="Password" required autocomplete="current-password">
                        </div>
                        <button class="btn btn-primary btn-user btn-block">Login</button>
                    </form>
                    <hr>
                    <div class="text-center"><a class="small" href="../index.php">&larr; Back to website</a></div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
