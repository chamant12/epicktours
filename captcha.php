<?php
/**
 * Captcha image for the contact form: 4 random characters, checked without
 * regard to upper/lower case. Look-alike characters (0/O, 1/I/L) are left out.
 * The answer is kept in the session for 15 minutes and works once.
 */
require __DIR__ . '/includes/config.php';

$alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 4; $i++) {
    $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
}
$_SESSION['captcha'] = ['code' => strtolower($code), 'time' => time()];

$w = 170;
$h = 56;
$img = imagecreatetruecolor($w, $h);
$bg = imagecolorallocate($img, 250, 246, 238);
imagefill($img, 0, 0, $bg);

// Background noise
for ($i = 0; $i < 400; $i++) {
    $c = imagecolorallocate($img, random_int(150, 220), random_int(150, 220), random_int(150, 220));
    imagesetpixel($img, random_int(0, $w - 1), random_int(0, $h - 1), $c);
}

// Each character drawn small, rotated, then scaled up at a random height
$colors = [[31, 74, 63], [47, 107, 58], [184, 135, 27], [30, 85, 82], [90, 60, 30]];
for ($i = 0; $i < 4; $i++) {
    $char = imagecreatetruecolor(14, 18);
    $cbg = imagecolorallocate($char, 250, 246, 238);
    imagefill($char, 0, 0, $cbg);
    [$r, $g, $b] = $colors[array_rand($colors)];
    imagestring($char, 5, 3, 1, $code[$i], imagecolorallocate($char, $r, $g, $b));
    $rot = imagerotate($char, random_int(-22, 22), $cbg);
    $cw = (int) (imagesx($rot) * 2.6);
    $ch = (int) (imagesy($rot) * 2.6);
    imagecopyresampled($img, $rot, 10 + $i * 38 + random_int(-3, 3), (int) (($h - $ch) / 2) + random_int(-4, 4), 0, 0, $cw, $ch, imagesx($rot), imagesy($rot));
    imagedestroy($char);
    imagedestroy($rot);
}

// Lines through the text
for ($i = 0; $i < 4; $i++) {
    $c = imagecolorallocate($img, random_int(100, 180), random_int(100, 180), random_int(100, 180));
    imagesetthickness($img, random_int(1, 2));
    imageline($img, 0, random_int(0, $h), $w, random_int(0, $h), $c);
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
imagepng($img);
imagedestroy($img);
