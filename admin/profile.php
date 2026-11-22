<?php
require __DIR__ . '/_init.php';
$adminTitle = 'My profile';
$activeNav = '';
$me = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');

    if (!password_verify($current, $me['password_hash'])) {
        $errors[] = 'Your current password is incorrect.';
    }
    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (q('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $me['id']])->fetch()) {
        $errors[] = 'Another user already has this email address.';
    }
    if ($new !== '' && strlen($new) < 8) {
        $errors[] = 'The new password must be at least 8 characters.';
    }
    if ($new !== '' && $new !== (string) ($_POST['confirm_password'] ?? '')) {
        $errors[] = 'The new passwords do not match.';
    }

    if (!$errors) {
        q('UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?', [$name, $email, $me['id']]);
        if ($new !== '') {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            session_regenerate_id(true);
        }
        flash('success', 'Profile updated.');
        redirect('profile.php');
    }
    $me = array_merge($me, ['name' => $name, 'email' => $email]);
}

require __DIR__ . '/_header.php';
?>
<h1 class="h3 mb-4 text-gray-800">My profile</h1>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" class="card shadow mb-4" style="max-width:640px">
    <div class="card-body">
        <?= csrf_field() ?>
        <p class="mb-3">Role: <span class="badge badge-primary"><?= e($me['role_name']) ?></span></p>
        <div class="form-group"><label>Name</label><input class="form-control" name="name" value="<?= e($me['name']) ?>" required></div>
        <div class="form-group"><label>Email (used to log in)</label><input type="email" class="form-control" name="email" value="<?= e($me['email']) ?>" required></div>
        <hr>
        <div class="form-row">
            <div class="form-group col-md-6"><label>New password</label><input type="password" class="form-control" name="new_password" minlength="8" autocomplete="new-password"><small class="form-text text-muted">Leave empty to keep your password.</small></div>
            <div class="form-group col-md-6"><label>Repeat new password</label><input type="password" class="form-control" name="confirm_password" autocomplete="new-password"></div>
        </div>
        <hr>
        <div class="form-group"><label>Current password *</label><input type="password" class="form-control" name="current_password" required autocomplete="current-password"><small class="form-text text-muted">Needed to save any change.</small></div>
        <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
    </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
