<?php
require __DIR__ . '/includes/config.php';
$currentPage = 'contact';
$pageTitle = str_replace("\n", ' ', block('contact', 'title'));

$countries = lines(block('contact', 'countries'));
$holidayTypes = array_merge(tour_categories(), ['Wildlife', 'Wellness', 'Corporate / MICE', 'Other']);
$travellerOptions = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10+'];

$errors = [];
$sent = false;
$old = [
    'name' => '', 'email' => '', 'country' => '', 'arrival' => '', 'departure' => '',
    'travellers' => '', 'type' => '', 'message' => '',
];

// Pre-fill from "Request a Quote" on a tour or a click on an experience
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!empty($_GET['tour']) && ($t = find_tour((string) $_GET['tour']))) {
        $old['message'] = "I'm interested in the \"{$t['title']}\" tour ({$t['days']} days / {$t['nights']} nights).";
        $old['type'] = $t['category'];
    } elseif (!empty($_GET['experience'])) {
        $old['message'] = 'I would like to include this experience: ' . mb_substr((string) $_GET['experience'], 0, 80) . '.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim((string) ($_POST[$k] ?? ''));
    }

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    }
    if (!empty($_POST['website'])) { // honeypot: bots fill hidden fields
        $errors['form'] = 'Could not send your enquiry.';
    }
    // Captcha: case-insensitive, valid for 15 minutes, single use
    $captcha = $_SESSION['captcha'] ?? null;
    unset($_SESSION['captcha']);
    $answer = strtolower(trim((string) ($_POST['captcha'] ?? '')));
    if (!$captcha || time() - $captcha['time'] > 900 || $answer === '' || !hash_equals($captcha['code'], $answer)) {
        $errors['captcha'] = 'The security code did not match. Please type the 4 characters in the new image.';
    }
    if ($old['name'] === '' || mb_strlen($old['name']) > 100) {
        $errors['name'] = 'Please enter your full name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 190) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (!in_array($old['country'], $countries, true)) {
        $errors['country'] = 'Please choose your country of residence.';
    }
    if (!in_array($old['travellers'], $travellerOptions, true)) {
        $errors['travellers'] = 'Please choose the number of travellers.';
    }
    if ($old['type'] !== '' && !in_array($old['type'], $holidayTypes, true)) {
        $errors['type'] = 'Please choose a holiday type.';
    }
    $validDate = function ($d) {
        if ($d === '') {
            return true;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt && $dt->format('Y-m-d') === $d;
    };
    if (!$validDate($old['arrival']) || !$validDate($old['departure'])) {
        $errors['arrival'] = 'Please enter valid dates.';
    } elseif ($old['arrival'] && $old['departure'] && $old['departure'] < $old['arrival']) {
        $errors['arrival'] = 'Departure must be after arrival.';
    }
    if (mb_strlen($old['message']) > 3000) {
        $errors['message'] = 'Please keep your message under 3000 characters.';
    }

    if (!$errors) {
        q('INSERT INTO enquiries (name, email, country, arrival, departure, travellers, holiday_type, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
            $old['name'], $old['email'], $old['country'],
            $old['arrival'] ?: null, $old['departure'] ?: null,
            $old['travellers'], $old['type'], $old['message'],
        ]);

        $enquiryId = (int) db()->lastInsertId();

        // Email the submission to the admin(s). The enquiry is already saved,
        // so a mail failure is only logged – the visitor still sees "thank you".
        $fmtDate = fn ($d) => $d ? date('d M Y', strtotime($d)) : '–';
        $rows = [
            'Name'            => $old['name'],
            'Email'           => $old['email'],
            'Country'         => $old['country'],
            'Arrival'         => $fmtDate($old['arrival']),
            'Departure'       => $fmtDate($old['departure']),
            'Travellers'      => $old['travellers'],
            'Type of holiday' => $old['type'] ?: '–',
        ];
        $table = '';
        foreach ($rows as $label => $value) {
            $table .= '<tr><td style="padding:6px 12px;background:#f3ecdd;font-weight:bold;white-space:nowrap">' . e($label) . '</td><td style="padding:6px 12px">' . e($value) . '</td></tr>';
        }
        $html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#1d2a24">'
            . '<h2 style="color:#1f4a3f;margin:0 0 12px">New tour enquiry</h2>'
            . '<table style="border-collapse:collapse;border:1px solid #e7dfcd">' . $table . '</table>'
            . '<h3 style="margin:18px 0 6px">Message</h3>'
            . '<p style="white-space:pre-wrap;margin:0">' . ($old['message'] !== '' ? nl2br(e($old['message'])) : '<em>No message</em>') . '</p>'
            . '<p style="margin-top:20px"><a href="' . e(site_url() . 'admin/enquiry.php?id=' . $enquiryId) . '" style="background:#d4a12a;color:#fff;padding:10px 18px;border-radius:20px;text-decoration:none">Open in admin</a></p>'
            . '<p style="color:#888;font-size:12px">Reply to this email to answer ' . e($old['name']) . ' directly.</p>'
            . '</div>';
        $result = send_mail(enquiry_recipients(), 'New tour enquiry from ' . $old['name'], $html, $old['email']);
        if ($result !== true) {
            error_log('Enquiry #' . $enquiryId . ' email failed: ' . $result);
        }

        $sent = true;
        unset($_SESSION['csrf']);
    }
}

$err = fn ($k) => isset($errors[$k]) ? '<small class="field-error">' . e($errors[$k]) . '</small>' : '';
require __DIR__ . '/includes/header.php';
?>

<section class="contact-section">
    <div class="container contact-layout">
        <div class="contact-intro">
            <h1><?= block_br('contact', 'title') ?></h1>
            <p><?= block_br('contact', 'intro') ?></p>
            <ul class="contact-details">
                <li><i class="fa-solid fa-phone"></i> <?= e(setting('phone')) ?></li>
                <li><i class="fa-solid fa-envelope"></i> <?= e(setting('email')) ?></li>
                <li><i class="fa-solid fa-location-dot"></i> <?= e(setting('address')) ?></li>
            </ul>
            <div class="photo contact-photo" style="<?= bg(block('contact', 'image')) ?>"></div>
        </div>

        <div class="form-card">
            <?php if ($sent): ?>
                <div class="success">
                    <i class="fa-solid fa-circle-check"></i>
                    <h2>Thank you, <?= e($old['name']) ?>!</h2>
                    <p><?= block_br('contact', 'success_message') ?></p>
                    <a href="tours.php" class="btn btn-gold">Browse tours</a>
                </div>
            <?php else: ?>
                <h2><?= e(block('contact', 'form_title')) ?></h2>
                <?php if (isset($errors['form'])): ?><div class="alert"><?= e($errors['form']) ?></div><?php endif; ?>
                <form method="post" action="contact.php" novalidate>
                    <?= csrf_field() ?>
                    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

                    <div class="form-row">
                        <label>Full Name <span class="req">*</span>
                            <input type="text" name="name" value="<?= e($old['name']) ?>" required maxlength="100">
                            <?= $err('name') ?>
                        </label>
                        <label>Email <span class="req">*</span>
                            <input type="email" name="email" value="<?= e($old['email']) ?>" required maxlength="190">
                            <?= $err('email') ?>
                        </label>
                    </div>

                    <label>Country of Residence <span class="req">*</span>
                        <select name="country" required>
                            <option value="">Select your country</option>
                            <?php foreach ($countries as $c): ?>
                                <option<?= $old['country'] === $c ? ' selected' : '' ?>><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $err('country') ?>
                    </label>

                    <span class="label">Travel Dates</span>
                    <div class="form-row">
                        <label class="sub">Arrival<input type="date" name="arrival" value="<?= e($old['arrival']) ?>" min="<?= date('Y-m-d') ?>"></label>
                        <label class="sub">Departure<input type="date" name="departure" value="<?= e($old['departure']) ?>" min="<?= date('Y-m-d') ?>"></label>
                    </div>
                    <?= $err('arrival') ?>

                    <div class="form-row">
                        <label>Number of Travellers <span class="req">*</span>
                            <select name="travellers" required>
                                <option value="">Select</option>
                                <?php foreach ($travellerOptions as $n): ?>
                                    <option<?= $old['travellers'] === $n ? ' selected' : '' ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('travellers') ?>
                        </label>
                        <label>Type of Holiday
                            <select name="type">
                                <option value="">Select</option>
                                <?php foreach ($holidayTypes as $h): ?>
                                    <option<?= $old['type'] === $h ? ' selected' : '' ?>><?= e($h) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('type') ?>
                        </label>
                    </div>

                    <label>Your Message
                        <textarea name="message" rows="4" maxlength="3000" placeholder="e.g. interests, special requirements, budget etc."><?= e($old['message']) ?></textarea>
                        <?= $err('message') ?>
                    </label>

                    <div class="field">
                        <label for="captchaInput" class="inline-label">Security code <span class="req">*</span></label>
                        <span class="captcha-row">
                            <img src="captcha.php?t=<?= time() ?>" alt="Security code" class="captcha-img" id="captchaImg" width="170" height="56">
                            <button type="button" class="captcha-refresh" id="captchaRefresh" title="Show a different code" aria-label="Show a different code"><i class="fa-solid fa-rotate"></i></button>
                            <input type="text" id="captchaInput" name="captcha" maxlength="4" required autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Type the 4 characters" class="captcha-input">
                        </span>
                        <small class="captcha-help">Not case sensitive.</small>
                        <?= $err('captcha') ?>
                    </div>

                    <button type="submit" class="btn btn-gold btn-block"><i class="fa-solid fa-paper-plane"></i> <?= e(block('contact', 'form_button')) ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
