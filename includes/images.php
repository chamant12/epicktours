<?php
/**
 * Image uploads: every uploaded image is resized to the size of the space it
 * is used in (see $IMAGE_SPECS in cms-fields.php), has its metadata stripped,
 * and is saved as an optimised WebP (or JPEG/PNG if the server's GD has no
 * WebP support). Files go to uploads/YYYY/MM/.
 */

/**
 * Clean up a stored image path: forward slashes, no leading slash, so
 * "/uploads/x.jpg", "uploads\x.jpg" and "uploads/x.jpg" all work.
 * Full http(s) URLs are returned unchanged.
 */
function image_path(?string $path): string
{
    $path = trim(str_replace('\\', '/', (string) $path));
    return preg_match('#^(https?:)?//#i', $path) ? $path : ltrim($path, '/');
}

/** True when the image can be shown: a file that exists, or an external URL. */
function image_exists(?string $path): bool
{
    $path = image_path($path);
    if ($path === '') {
        return false;
    }
    return preg_match('#^(https?:)?//#i', $path) || (!str_contains($path, '..') && is_file(ROOT . '/' . $path));
}

/** Web path of the site root, e.g. "/" or "/epicktours/". Same from admin and public pages. */
function base_path(): string
{
    static $base = null;
    return $base ??= rtrim((string) parse_url(site_url(), PHP_URL_PATH), '/') . '/';
}

/**
 * URL of a stored image, from the site root (works on every page, admin
 * included). "?v=<file time>" makes browsers and CDNs fetch a replaced file
 * straight away instead of showing an old cached copy.
 */
function img_url(?string $path, string $size = 'large'): string
{
    $path = image_path($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }
    if ($size === 'small') {
        $small = small_variant($path);
        if (is_file(ROOT . '/' . $small)) {
            $path = $small;
        }
    }
    $time = @filemtime(ROOT . '/' . $path);
    return base_path() . str_replace(['%2F', '%40'], ['/', '@'], rawurlencode($path)) . ($time ? '?v=' . $time : '');
}

/** Absolute URL of a stored image (for og:image and emails). */
function img_abs_url(?string $path): string
{
    $url = img_url($path);
    if ($url === '' || preg_match('#^(https?:)?//#i', $url)) {
        return $url;
    }
    $parts = parse_url(site_url());
    return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '') . $url;
}

/**
 * Inline style for every photo box on the site (banner, cards, gallery…).
 * When an image is uploaded it always fills the box, overriding the empty
 * gradient placeholder and any other CSS. No image → '' → the placeholder shows.
 */
function bg(?string $path, string $size = 'large'): string
{
    if (!image_exists($path)) {
        return '';
    }
    $url = e(img_url($path, $size));
    return "--img:url('{$url}');"
        . " background-image:url('{$url}') !important;"
        . ' background-size:cover !important;'
        . ' background-position:center !important;'
        . ' background-repeat:no-repeat !important';
}

function small_variant(string $path): string
{
    return preg_replace('/(\.[a-z0-9]+)$/i', '-sm$1', $path);
}

/** Delete a stored image and its small copy (only inside uploads/). */
function delete_image(?string $path): void
{
    if (!$path || !str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
        return;
    }
    foreach ([$path, small_variant($path)] as $p) {
        if (is_file(ROOT . '/' . $p)) {
            @unlink(ROOT . '/' . $p);
        }
    }
}

/** Human-readable size for an image spec, e.g. "1920 × 1000". */
function spec_label(string $specName): string
{
    global $IMAGE_SPECS;
    $s = $IMAGE_SPECS[$specName];
    return $s['w'] . ' × ' . $s['h'] . ' px' . ($s['mode'] === 'cover' ? ' (cropped to fit)' : ' (fitted inside)');
}

/**
 * Process one uploaded file ($_FILES entry) and return its stored path.
 * Throws RuntimeException with a message suitable for the user.
 */
function save_uploaded_image(array $file, string $specName): string
{
    global $IMAGE_SPECS;
    $spec = $IMAGE_SPECS[$specName] ?? null;
    if (!$spec) {
        throw new RuntimeException('Unknown image type.');
    }
    if (!extension_loaded('gd')) {
        throw new RuntimeException('The server is missing the PHP GD extension needed to process images.');
    }

    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('The image is too large to upload.');
    }
    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The image could not be uploaded. Please try again.');
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('The image is larger than ' . round(MAX_UPLOAD_BYTES / 1048576) . ' MB.');
    }

    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
    if (!$info || !in_array($info[2], $types, true)) {
        throw new RuntimeException('Please upload a JPG, PNG, GIF or WebP image.');
    }
    if ($info[0] * $info[1] > 40000000) {
        throw new RuntimeException('The image has too many pixels (max 40 megapixels).');
    }

    @ini_set('memory_limit', '512M');
    $src = load_image($file['tmp_name'], $info[2]);
    if (!$src) {
        throw new RuntimeException('The image file seems to be damaged.');
    }
    if ($info[2] === IMAGETYPE_JPEG) {
        $src = fix_orientation($src, $file['tmp_name']);
    }

    $dir = 'uploads/' . date('Y/m');
    if (!is_dir(ROOT . '/' . $dir) && !mkdir(ROOT . '/' . $dir, 0775, true)) {
        throw new RuntimeException('The uploads folder is not writable.');
    }

    $alpha = $info[2] !== IMAGETYPE_JPEG && has_transparency($src);
    $ext = function_exists('imagewebp') ? 'webp' : ($alpha ? 'png' : 'jpg');
    $path = $dir . '/' . bin2hex(random_bytes(8)) . '.' . $ext;

    write_image(resize_image($src, $spec['w'], $spec['h'], $spec['mode']), ROOT . '/' . $path, $ext);
    if (!empty($spec['small'])) {
        [$sw, $sh] = $spec['small'];
        write_image(resize_image($src, $sw, $sh, $spec['mode']), ROOT . '/' . small_variant($path), $ext);
    }
    imagedestroy($src);

    return $path;
}

function load_image(string $file, int $type)
{
    switch ($type) {
        case IMAGETYPE_JPEG: return @imagecreatefromjpeg($file);
        case IMAGETYPE_PNG:  return @imagecreatefrompng($file);
        case IMAGETYPE_GIF:  return @imagecreatefromgif($file);
        case IMAGETYPE_WEBP: return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file) : false;
    }
    return false;
}

/** Rotate phone photos that are stored sideways with an EXIF flag. */
function fix_orientation($img, string $file)
{
    if (!function_exists('exif_read_data')) {
        return $img;
    }
    $exif = @exif_read_data($file);
    $angle = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
    if ($angle) {
        $rotated = imagerotate($img, $angle, 0);
        if ($rotated) {
            imagedestroy($img);
            return $rotated;
        }
    }
    return $img;
}

function has_transparency($img): bool
{
    if (!imageistruecolor($img)) {
        return imagecolortransparent($img) >= 0;
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $step = max(1, (int) (min($w, $h) / 50));
    for ($x = 0; $x < $w; $x += $step) {
        for ($y = 0; $y < $h; $y += $step) {
            if ((imagecolorat($img, $x, $y) >> 24) & 0x7F) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Resize to the target space. "cover" crops the centre to the exact aspect
 * ratio; "contain" fits the whole image inside. Never enlarges small images.
 */
function resize_image($src, int $tw, int $th, string $mode)
{
    $sw = imagesx($src);
    $sh = imagesy($src);
    $sx = $sy = 0;

    if ($mode === 'cover') {
        $targetRatio = $tw / $th;
        if ($sw / $sh > $targetRatio) {      // too wide: crop the sides
            $cw = (int) round($sh * $targetRatio);
            $ch = $sh;
            $sx = (int) (($sw - $cw) / 2);
        } else {                              // too tall: crop top and bottom
            $cw = $sw;
            $ch = (int) round($sw / $targetRatio);
            $sy = (int) (($sh - $ch) / 2);
        }
        $scale = min(1, $tw / $cw);
        $dw = max(1, (int) round($cw * $scale));
        $dh = max(1, (int) round($ch * $scale));
    } else {
        $cw = $sw;
        $ch = $sh;
        $scale = min(1, $tw / $sw, $th / $sh);
        $dw = max(1, (int) round($sw * $scale));
        $dh = max(1, (int) round($sh * $scale));
    }

    $dst = imagecreatetruecolor($dw, $dh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $dw, $dh, $cw, $ch);
    return $dst;
}

function write_image($img, string $file, string $ext): void
{
    $ok = false;
    if ($ext === 'webp') {
        $ok = imagewebp($img, $file, IMAGE_QUALITY);
    } elseif ($ext === 'png') {
        $ok = imagepng($img, $file, 9);
    } else {
        // JPEG has no transparency: flatten onto white
        $flat = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        imageinterlace($flat, true); // progressive JPEG
        $ok = imagejpeg($flat, $file, IMAGE_QUALITY);
        imagedestroy($flat);
    }
    imagedestroy($img);
    if (!$ok) {
        throw new RuntimeException('Could not save the processed image.');
    }
}

/**
 * Handle an image field in a CMS form: replace on upload, clear when
 * "remove" is ticked, otherwise keep the current image. Returns the new path.
 */
function handle_image_field(string $name, string $current, string $specName): string
{
    $file = $_FILES[$name] ?? null;
    if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $new = save_uploaded_image($file, $specName);
        delete_image($current);
        return $new;
    }
    if (!empty($_POST[$name . '_remove'])) {
        delete_image($current);
        return '';
    }
    return $current;
}
