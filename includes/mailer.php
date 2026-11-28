<?php
/**
 * Sending email. Uses SMTP when an SMTP host is set in Admin → Settings
 * (recommended – far less likely to land in spam), otherwise PHP's mail().
 */

/**
 * Send an HTML email (with a plain-text copy).
 * Returns true on success or an error message string on failure.
 */
function send_mail(array $to, string $subject, string $html, ?string $replyTo = null, array $cc = [])
{
    $result = send_mail_now($to, $subject, $html, $replyTo, $cc);
    // Remember the outcome so a failure shows up in the admin instead of only
    // in the server's error log. A later successful send clears it.
    try {
        $value = $result === true ? '' : date('Y-m-d H:i') . ' | ' . $subject . ' | ' . $result;
        q('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', ['mail_last_error', $value]);
    } catch (Throwable $ex) {
        // never let logging break sending
    }
    if ($result !== true) {
        error_log('Email "' . $subject . '" failed: ' . $result);
    }
    return $result;
}

/** Last email failure as [when, subject, error], or null when the last send worked. */
function mail_last_error(): ?array
{
    $v = (string) q("SELECT svalue FROM settings WHERE skey = 'mail_last_error'")->fetchColumn();
    return $v === '' ? null : array_pad(explode(' | ', $v, 3), 3, '');
}

/**
 * The address emails are sent from.
 * Mailbox providers (Zoho, Gmail, Outlook/Office 365, Yahoo) refuse to send
 * "from" any address other than the one you log in with ("550 Sender is not
 * allowed to relay"), so for them the SMTP username is used automatically.
 * SMTP_FROM in config.local.php overrides this (e.g. for a Zoho alias).
 */
function mail_from_email(): string
{
    if (defined('SMTP_FROM') && filter_var(SMTP_FROM, FILTER_VALIDATE_EMAIL)) {
        return SMTP_FROM;
    }
    $smtp = smtp_config();
    $mailboxHost = preg_match('/(^|\.)(zoho(mail)?|gmail|googlemail|office365|outlook|hotmail|live|yahoo)\.[a-z.]+$/i', $smtp['host']);
    if ($mailboxHost && filter_var($smtp['user'], FILTER_VALIDATE_EMAIL)) {
        return $smtp['user'];
    }
    return setting('mail_from_email') ?: setting('email');
}

/** The actual sending – use send_mail(). */
function send_mail_now(array $to, string $subject, string $html, ?string $replyTo = null, array $cc = [])
{
    $valid = fn (array $list) => array_values(array_unique(array_filter(array_map('trim', $list), fn ($a) => filter_var($a, FILTER_VALIDATE_EMAIL))));
    $to = $valid($to);
    if (!$to) {
        return 'No valid recipient email address.';
    }
    // CC addresses that aren't already in To (case-insensitive)
    $lowerTo = array_map('strtolower', $to);
    $cc = array_values(array_filter($valid($cc), fn ($a) => !in_array(strtolower($a), $lowerTo, true)));

    $fromEmail = mail_from_email();
    $fromName = setting('mail_from_name') ?: setting('site_name');
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return 'The "From" email address in Settings is not valid.';
    }
    $clean = fn ($s) => trim(str_replace(["\r", "\n"], ' ', (string) $s));
    $encode = fn ($s) => '=?UTF-8?B?' . base64_encode($clean($s)) . '?=';

    $boundary = 'b' . bin2hex(random_bytes(12));
    $text = trim(html_entity_decode(strip_tags(preg_replace(['/<br\s*\/?>/i', '/<\/(p|tr|h\d|div)>/i'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
    $text = preg_replace("/[ \t]+/", ' ', preg_replace("/\n{3,}/", "\n\n", $text));

    $domain = substr(strrchr($fromEmail, '@'), 1);
    $headers = [
        'Date: ' . date('r'),
        'From: ' . $encode($fromName) . ' <' . $fromEmail . '>',
        'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . $domain . '>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $clean($replyTo);
    }
    if ($cc) {
        $headers[] = 'Cc: ' . implode(', ', $cc);
    }

    $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($text))
        . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html))
        . "--{$boundary}--\r\n";

    try {
        if (smtp_config()['host'] !== '') {
            $message = implode("\r\n", array_merge($headers, ['To: ' . implode(', ', $to), 'Subject: ' . $encode($subject)])) . "\r\n\r\n" . $body;
            smtp_send($fromEmail, array_merge($to, $cc), $message); // every To + Cc address is a recipient
        } else {
            if (!@mail(implode(', ', $to), $encode($subject), $body, implode("\r\n", $headers))) {
                return 'The server could not send the email with PHP mail(). Set up SMTP in Settings.';
            }
        }
    } catch (RuntimeException $ex) {
        return $ex->getMessage();
    }
    return true;
}

/**
 * SMTP connection details. SMTP_* constants in includes/config.local.php
 * (e.g. Amazon SES credentials) take priority over Admin → Settings, so the
 * password never has to be stored in the database.
 */
function smtp_config(): array
{
    if (smtp_from_config()) {
        return [
            'host' => (string) SMTP_HOST,
            'port' => (int) (defined('SMTP_PORT') ? SMTP_PORT : 587),
            'enc'  => defined('SMTP_ENCRYPTION') ? (string) SMTP_ENCRYPTION : 'tls',
            'user' => defined('SMTP_USERNAME') ? (string) SMTP_USERNAME : '',
            'pass' => defined('SMTP_PASSWORD') ? (string) SMTP_PASSWORD : '',
        ];
    }
    return [
        'host' => setting('smtp_host'),
        'port' => (int) (setting('smtp_port') ?: 587),
        'enc'  => setting('smtp_encryption') ?: 'tls',
        'user' => setting('smtp_username'),
        'pass' => setting('smtp_password'),
    ];
}

/** True when the SMTP server is set in config.local.php rather than in Settings. */
function smtp_from_config(): bool
{
    return defined('SMTP_HOST') && SMTP_HOST !== '';
}

/** Minimal SMTP client (STARTTLS/SSL + AUTH LOGIN). Throws RuntimeException. */
function smtp_send(string $from, array $to, string $message): void
{
    ['host' => $host, 'port' => $port, 'enc' => $enc, 'user' => $user, 'pass' => $pass] = smtp_config();
    $isSes = (bool) preg_match('/^email-smtp\.[a-z0-9-]+\.amazonaws\.com$/i', $host);
    $isZoho = (bool) preg_match('/^smtp(pro)?\.zoho(mail)?\.[a-z.]+$/i', $host);

    $fp = @stream_socket_client(($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 15);
    if (!$fp) {
        throw new RuntimeException("Could not connect to the SMTP server {$host}:{$port} ({$errstr}).");
    }
    stream_set_timeout($fp, 20);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (?string $command, array $expect, ?string $label = null) use ($fp, $read): string {
        if ($command !== null) {
            fwrite($fp, $command . "\r\n");
        }
        $reply = $read();
        if (!in_array((int) substr($reply, 0, 3), $expect, true)) {
            if ($label !== null) {
                $shown = $label;
            } elseif ($command === null) {
                $shown = 'connecting';
            } elseif (strlen($command) >= 80) {
                $shown = 'sending the message';
            } else {
                $shown = $command;
            }
            throw new RuntimeException('SMTP error after "' . $shown . '": ' . trim($reply ?: 'no reply'));
        }
        return $reply;
    };

    try {
        $cmd(null, [220]);
        $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');
        $cmd($ehlo, [250]);
        if ($enc === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Could not start TLS encryption with the SMTP server.');
            }
            $cmd($ehlo, [250]);
        }
        if ($user !== '') {
            $cmd('AUTH LOGIN', [334], 'login');
            $cmd(base64_encode($user), [334], 'login');
            try {
                $cmd(base64_encode($pass), [235], 'login');
            } catch (RuntimeException $ex) {
                $hint = '';
                if ($isSes) {
                    $hint = ' For Amazon SES use the SMTP credentials created in the SES console (SMTP settings → Create SMTP credentials), not IAM access keys, and make sure they belong to the same region as the SMTP server.';
                } elseif ($isZoho) {
                    $hint = ' For Zoho Mail: the username is the full email address; if two-factor authentication is on, use an app-specific password (Zoho Accounts → Security → App Passwords), not the normal password;'
                        . ' a company/domain mailbox uses smtppro.zoho.com, and accounts in the India or EU data centre use smtppro.zoho.in / smtppro.zoho.eu.';
                }
                throw new RuntimeException('The SMTP username or password was rejected.' . $hint);
            }
        }
        $cmd('MAIL FROM:<' . $from . '>', [250]);
        foreach ($to as $rcpt) {
            $cmd('RCPT TO:<' . $rcpt . '>', [250, 251]);
        }
        $cmd('DATA', [354]);
        // Normalise line endings and escape lines starting with a dot
        $message = preg_replace('/(?<!\r)\n/', "\r\n", $message);
        $message = preg_replace('/^\./m', '..', $message);
        $cmd($message . "\r\n.", [250]);
        fwrite($fp, "QUIT\r\n");
    } catch (RuntimeException $ex) {
        if ($isSes && stripos($ex->getMessage(), 'not verified') !== false) {
            throw new RuntimeException($ex->getMessage() . ' – Amazon SES only sends from verified addresses/domains. While your SES account is in the sandbox, every recipient must be verified too (or request production access in the SES console).');
        }
        if (stripos($ex->getMessage(), 'relay') !== false || ($isZoho && stripos($ex->getMessage(), 'MAIL FROM') !== false)) {
            throw new RuntimeException($ex->getMessage() . ' – the mail server only accepts emails "from" the mailbox you log in with (' . ($user ?: 'the SMTP username') . ') or one of its aliases. Emails were sent from ' . $from . '.');
        }
        throw $ex;
    } finally {
        fclose($fp);
    }
}

/** Who gets a copy (CC) of contact form notifications – Settings, "none" to turn off. */
function enquiry_cc(): array
{
    $value = trim(setting('enquiry_email_cc'));
    if (strtolower($value) === 'none') {
        return [];
    }
    return array_values(array_filter(array_map('trim', explode(',', $value))));
}

/** Who gets contact form notifications: the Settings list, or every active admin. */
function enquiry_recipients(): array
{
    $list = array_filter(array_map('trim', explode(',', setting('enquiry_email_to'))));
    if ($list) {
        return $list;
    }
    return q("SELECT u.email FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'admin' AND u.is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
}

/** Absolute base URL of the site, e.g. https://example.com/ (from Settings, else the request). */
function site_url(): string
{
    $url = setting('site_url');
    if ($url === '') {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = preg_replace('/[^a-z0-9.\-:]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        if (defined('IN_ADMIN')) {
            $dir = rtrim(dirname($dir), '/');
        }
        $url = ($https ? 'https' : 'http') . '://' . $host . $dir;
    }
    return rtrim($url, '/') . '/';
}
