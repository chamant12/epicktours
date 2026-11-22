<?php
require __DIR__ . '/_init.php';
require_can('users');
$activeNav = 'users';

$roles = q('SELECT * FROM roles ORDER BY id')->fetchAll();
$roleIds = array_column($roles, 'id');
$me = current_user();

/** Number of active admins, optionally ignoring one user. */
function active_admins(int $exceptId = 0): int
{
    return (int) q("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'admin' AND u.is_active = 1 AND u.id <> ?", [$exceptId])->fetchColumn();
}

$edit = $_GET['edit'] ?? null;
$user = null;
if ($edit !== null && $edit !== 'new') {
    $user = q('SELECT * FROM users WHERE id = ?', [(int) $edit])->fetch();
    if (!$user) {
        flash('danger', 'User not found.');
        redirect('users.php');
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $roleId = (int) ($_POST['role_id'] ?? 0);
    $active = !empty($_POST['is_active']) ? 1 : 0;
    $password = (string) ($_POST['password'] ?? '');
    $adminRoleId = (int) q("SELECT id FROM roles WHERE slug = 'admin'")->fetchColumn();

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Please enter a name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (q('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $user['id'] ?? 0])->fetch()) {
        $errors[] = 'Another user already has this email address.';
    }
    if (!in_array($roleId, array_map('intval', $roleIds), true)) {
        $errors[] = 'Please choose a role.';
    }
    if ((!$user || $password !== '') && strlen($password) < 8) {
        $errors[] = 'The password must be at least 8 characters.';
    }
    if ($user && (int) $user['id'] === (int) $me['id'] && (!$active || $roleId !== $adminRoleId)) {
        $errors[] = 'You cannot deactivate your own account or remove your own admin role.';
    }
    if ($user && ($roleId !== $adminRoleId || !$active) && (int) $user['role_id'] === $adminRoleId && active_admins((int) $user['id']) === 0) {
        $errors[] = 'There must always be at least one active administrator.';
    }

    if (!$errors) {
        if ($user) {
            q('UPDATE users SET name = ?, email = ?, role_id = ?, is_active = ?, updated_at = NOW() WHERE id = ?', [$name, $email, $roleId, $active, $user['id']]);
            if ($password !== '') {
                q('UPDATE users SET password_hash = ?, failed_logins = 0, locked_until = NULL WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            flash('success', 'User updated.');
        } else {
            q('INSERT INTO users (name, email, role_id, is_active, password_hash) VALUES (?, ?, ?, ?, ?)', [$name, $email, $roleId, $active, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'User created. Share the email and password with them so they can log in.');
        }
        redirect('users.php');
    }
    $user = array_merge($user ?: ['id' => 0], ['name' => $name, 'email' => $email, 'role_id' => $roleId, 'is_active' => $active]);
    $edit = $user['id'] ?: 'new';
}

// ----- Edit form -----
if ($edit !== null) {
    if (!$user) {
        // New users default to Content Writer unless ?role=admin is asked for
        $wanted = ($_GET['role'] ?? '') === 'admin' ? 'admin' : 'content_writer';
        $defaultRole = 0;
        foreach ($roles as $role) {
            if ($role['slug'] === $wanted) {
                $defaultRole = (int) $role['id'];
            }
        }
        $user = ['id' => 0, 'name' => '', 'email' => '', 'role_id' => $defaultRole, 'is_active' => 1];
    }
    $adminTitle = $user['id'] ? 'Edit user' : 'New user';
    if (!$user['id']) {
        foreach ($roles as $role) {
            if ((int) $role['id'] === (int) $user['role_id']) {
                $adminTitle = 'New ' . strtolower($role['name']);
            }
        }
    }
    require __DIR__ . '/_header.php';
    ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= e($adminTitle) ?></h1>
        <a href="users.php" class="btn btn-sm btn-light">All users</a>
    </div>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" action="users.php?edit=<?= $user['id'] ?: 'new' ?>" class="card shadow mb-4" style="max-width:640px">
        <div class="card-body">
            <?= csrf_field() ?>
            <div class="form-group"><label>Name *</label><input class="form-control" name="name" value="<?= e($user['name']) ?>" required maxlength="100"></div>
            <div class="form-group"><label>Email (used to log in) *</label><input type="email" class="form-control" name="email" value="<?= e($user['email']) ?>" required></div>
            <div class="form-group">
                <label>Role *</label>
                <?php foreach ($roles as $role): ?>
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" id="role<?= (int) $role['id'] ?>" name="role_id" value="<?= (int) $role['id'] ?>"<?= (int) $user['role_id'] === (int) $role['id'] ? ' checked' : '' ?>>
                        <label class="custom-control-label" for="role<?= (int) $role['id'] ?>">
                            <strong><?= e($role['name']) ?></strong>
                            <small class="text-muted d-block"><?= $role['slug'] === 'admin' ? 'Everything: content, enquiries, users and settings.' : 'Edits pages, tours, destinations, experiences and images. Cannot see enquiries, users or settings.' ?></small>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-group">
                <label><?= $user['id'] ? 'New password' : 'Password *' ?></label>
                <input type="password" class="form-control" name="password" minlength="8" autocomplete="new-password" <?= $user['id'] ? '' : 'required' ?>>
                <small class="form-text text-muted"><?= $user['id'] ? 'Leave empty to keep the current password. ' : '' ?>At least 8 characters.</small>
            </div>
            <?= switch_field('is_active', 'Account active (can log in)', (bool) $user['is_active']) ?>
            <button class="btn btn-primary"><i class="fas fa-save"></i> Save user</button>
            <a href="users.php" class="btn btn-light">Cancel</a>
        </div>
    </form>
    <?php
    require __DIR__ . '/_footer.php';
    exit;
}

// ----- List -----
$adminTitle = 'Users';
$users = q('SELECT u.*, r.name AS role_name, r.slug AS role FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.is_active DESC, r.id, u.name')->fetchAll();
require __DIR__ . '/_header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Users</h1>
        <p class="text-muted small mb-0">Users are never deleted – deactivate an account to stop someone logging in.</p>
    </div>
    <div>
        <a href="users.php?edit=new&amp;role=content_writer" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-user-pen fa-sm"></i> New content writer</a>
        <a href="users.php?edit=new&amp;role=admin" class="btn btn-sm btn-outline-primary shadow-sm"><i class="fas fa-user-shield fa-sm"></i> New administrator</a>
    </div>
</div>
<div class="card shadow mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr class="<?= $u['is_active'] ? '' : 'text-muted' ?>">
                    <td class="font-weight-bold"><?= e($u['name']) ?><?= (int) $u['id'] === (int) $me['id'] ? ' <span class="badge badge-light">You</span>' : '' ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'primary' : 'info' ?>"><?= e($u['role_name']) ?></span></td>
                    <td><?= $u['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Deactivated</span>' ?></td>
                    <td><small><?= $u['last_login_at'] ? e(date('d M Y, H:i', strtotime($u['last_login_at']))) : 'Never' ?></small></td>
                    <td class="text-right"><a href="users.php?edit=<?= (int) $u['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-pen"></i> Edit</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
