<?php
/**
 * One-time installer: creates the tables, adds the two user roles, loads the
 * starting content and creates the first admin. It locks itself once an
 * admin exists. Delete this file after installing.
 */
define('INSTALLING', true);
require __DIR__ . '/includes/config.php';

$error = '';
$done = false;

try {
    db();
    $installed = (bool) db()->query("SHOW TABLES LIKE 'users'")->fetch()
        && (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
} catch (PDOException $ex) {
    $installed = false;
    $error = 'Could not connect to the database "' . DB_NAME . '". Create the database first and check the settings in includes/config.php. (' . $ex->getMessage() . ')';
}

if (!$installed && !$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Session expired, please try again.';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter your name and a valid email.';
    } elseif (strlen($password) < 8) {
        $error = 'The password must be at least 8 characters.';
    } else {
        try {
            install_database($name, $email, $password);
            $done = true;
        } catch (Throwable $ex) {
            $error = 'Installation failed: ' . $ex->getMessage();
        }
    }
}

function install_database(string $name, string $email, string $password): void
{
    $pdo = db();
    $sql = file_get_contents(__DIR__ . '/install/schema.sql');
    $sql = preg_replace('/^--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        $pdo->exec($statement);
    }

    // Roles
    q("INSERT IGNORE INTO roles (slug, name) VALUES ('admin', 'Administrator'), ('content_writer', 'Content Writer')");
    $adminRole = (int) q("SELECT id FROM roles WHERE slug = 'admin'")->fetchColumn();

    // Starting content (only into empty tables)
    require __DIR__ . '/install/seed-data.php';

    if (!q('SELECT COUNT(*) FROM destinations')->fetchColumn()) {
        $i = 0;
        foreach ($DESTINATIONS as $slug => $d) {
            q('INSERT INTO destinations (slug, name, description, sort_order) VALUES (?, ?, ?, ?)', [$slug, $d['name'], $d['text'], ++$i]);
        }
    }
    if (!q('SELECT COUNT(*) FROM experiences')->fetchColumn()) {
        foreach ($EXPERIENCES as $i => $x) {
            q('INSERT INTO experiences (name, icon, description, sort_order) VALUES (?, ?, ?, ?)', [$x['name'], $x['icon'], $x['text'], $i + 1]);
        }
    }
    if (!q('SELECT COUNT(*) FROM themes')->fetchColumn()) {
        foreach ($THEMES as $i => $t) {
            q('INSERT INTO themes (name, icon, link_category, sort_order) VALUES (?, ?, ?, ?)', [$t['name'], $t['icon'], $t['cat'], $i + 1]);
        }
    }
    if (!q('SELECT COUNT(*) FROM tours')->fetchColumn()) {
        foreach ($TOURS as $i => $t) {
            q('INSERT INTO tours (slug, title, category, days, nights, price, summary, highlights, itinerary, inclusions, exclusions, hotels, destinations, is_featured, sort_order)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $t['slug'], $t['title'], $t['category'], $t['days'], $t['nights'], $t['price'], $t['summary'],
                json_encode($t['highlights']), json_encode($t['itinerary']), json_encode($t['inclusions']),
                json_encode($t['exclusions']), json_encode($t['hotels']), json_encode($t['destinations']),
                $t['featured'] ? 1 : 0, $i + 1,
            ]);
        }
    }

    q('INSERT INTO users (role_id, name, email, password_hash) VALUES (?, ?, ?, ?)', [
        $adminRole, $name, $email, password_hash($password, PASSWORD_DEFAULT),
    ]);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install EPICK Tours</title>
    <link rel="stylesheet" href="admin/assets/vendor/sb-admin-2.min.css">
</head>
<body class="bg-gradient-primary">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-5">
                    <h1 class="h4 text-gray-900 mb-4 text-center">Install EPICK Tours</h1>

                    <?php if ($done): ?>
                        <div class="alert alert-success">Installation complete. <strong>Delete install.php from the server now.</strong></div>
                        <a href="admin/login.php" class="btn btn-primary btn-block">Go to admin login</a>
                    <?php elseif ($installed): ?>
                        <div class="alert alert-info">The site is already installed. Delete install.php from the server.</div>
                        <a href="admin/login.php" class="btn btn-primary btn-block">Go to admin login</a>
                    <?php else: ?>
                        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                        <p class="small text-muted">This creates the database tables, loads the starting tours and content, and creates the first administrator account.</p>
                        <form method="post">
                            <?= csrf_field() ?>
                            <div class="form-group"><label>Your name</label><input class="form-control" name="name" required value="<?= e($_POST['name'] ?? '') ?>"></div>
                            <div class="form-group"><label>Email (used to log in)</label><input class="form-control" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
                            <div class="form-group"><label>Password (min. 8 characters)</label><input class="form-control" type="password" name="password" required minlength="8"></div>
                            <button class="btn btn-primary btn-block">Install</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
