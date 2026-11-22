<?php
require __DIR__ . '/_init.php';
require_can('settings');
$adminTitle = 'Settings';
$activeNav = 'settings';

$current = q('SELECT skey, svalue FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);

// Send a test email to the logged-in admin using the saved settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'test_email') {
    $me = current_user();
    $result = send_mail(
        [$me['email']],
        'Test email from ' . setting('site_name'),
        '<p>Hello ' . e($me['name']) . ',</p><p>This is a test email from your website. If you are reading this, contact form notifications will be delivered.</p>'
    );
    if ($result === true) {
        flash('success', 'Test email sent to ' . $me['email'] . '. Check the inbox (and the spam folder).');
    } else {
        flash('danger', 'The test email could not be sent: ' . $result);
    }
    redirect('settings.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    foreach ($SETTINGS_FIELDS as $fields) {
        foreach ($fields as $key => $f) {
            if ($f['type'] === 'image') {
                try {
                    $value = handle_image_field($key, $current[$key] ?? '', $f['spec']);
                } catch (RuntimeException $ex) {
                    $errors[] = $f['label'] . ': ' . $ex->getMessage();
                    continue;
                }
            } elseif ($f['type'] === 'password') {
                $value = (string) ($_POST[$key] ?? '');
                if ($value === '') {
                    continue; // keep the saved password
                }
            } else {
                $value = trim((string) ($_POST[$key] ?? ''));
            }

            if ($f['type'] === 'select' && !isset($f['options'][$value])) {
                $value = $f['default'];
            }
            if ($key === 'enquiry_email_to' && $value !== '') {
                $emails = array_filter(array_map('trim', explode(',', $value)));
                $bad = array_filter($emails, fn ($m) => !filter_var($m, FILTER_VALIDATE_EMAIL));
                if ($bad) {
                    $errors[] = 'Send new enquiries to: "' . implode('", "', $bad) . '" is not a valid email address.';
                    continue;
                }
                $value = implode(', ', $emails);
            }
            if ($key === 'mail_from_email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[] = '"From" email address is not valid.';
                continue;
            }
            if ($key === 'site_url' && $value !== '' && !preg_match('#^https?://[^\s/]+#i', $value)) {
                $errors[] = 'Website address must start with http:// or https://';
                continue;
            }
            if ($key === 'smtp_port' && $value !== '' && !ctype_digit($value)) {
                $errors[] = 'SMTP port must be a number.';
                continue;
            }
            q('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [$key, $value]);
        }
    }
    foreach ($errors as $err) {
        flash('danger', $err);
    }
    flash('success', $errors ? 'Other settings saved.' : 'Settings saved.');
    redirect('settings.php');
}

require __DIR__ . '/_header.php';
?>
<h1 class="h3 mb-4 text-gray-800">Settings</h1>

<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php foreach ($SETTINGS_FIELDS as $section => $fields): ?>
        <div class="card shadow mb-4 section-card">
            <div class="card-header py-3"><h6 class="font-weight-bold text-primary"><?= e($section) ?></h6></div>
            <div class="card-body">
                <?php foreach ($fields as $key => $f): ?>
                    <?php
                    $value = $current[$key] ?? null;
                    if ($value === null || ($value === '' && !in_array($f['type'], ['image', 'password'], true))) {
                        $value = $f['default'];
                    }
                    ?>
                    <?= cms_field($key, $f, (string) $value) ?>
                <?php endforeach; ?>
                <?php if ($section === 'Contact form emails'): ?>
                    <p class="small text-muted mb-0">
                        Enquiries currently go to: <strong><?= e(implode(', ', enquiry_recipients()) ?: 'nobody') ?></strong>.
                        Save first, then use <em>Send test email</em> below to check delivery.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="mb-4"><button class="btn btn-primary"><i class="fas fa-save"></i> Save settings</button></div>
</form>

<form method="post" class="mb-5">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="test_email">
    <button class="btn btn-outline-secondary"><i class="fas fa-paper-plane"></i> Send test email to <?= e(current_user()['email']) ?></button>
</form>

<?php require __DIR__ . '/_footer.php'; ?>
