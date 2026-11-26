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
        throw new RuntimeException('The image is bigger than this server accepts (' . mb_label(upload_limit_bytes()) . '). Reload the page (Ctrl+F5) so large photos are shrunk automatically before upload, then try again.');
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

    return process_image($file['tmp_name'], $info[2], $specName);
}

/** A php.ini size such as "2M", "512K" or "1G" in bytes (0 = unlimited). */
function ini_bytes(string $key): int
{
    $v = trim((string) ini_get($key));
    if ($v === '' || $v === '-1') {
        return 0;
    }
    $n = (float) $v;
    switch (strtolower(substr($v, -1))) {
        case 'g': $n *= 1024;
        // no break
        case 'm': $n *= 1024;
        // no break
        case 'k': $n *= 1024;
    }
    return (int) $n;
}

/**
 * Biggest single file the server will actually accept: the smaller of PHP's
 * upload_max_filesize / post_max_size and the CMS's MAX_UPLOAD_BYTES.
 */
function upload_limit_bytes(): int
{
    $limits = array_filter([ini_bytes('upload_max_filesize'), ini_bytes('post_max_size'), MAX_UPLOAD_BYTES]);
    return (int) min($limits);
}

/** Total size of one form post the server accepts (post_max_size). */
function post_limit_bytes(): int
{
    return ini_bytes('post_max_size') ?: 256 * 1048576;
}

/** "2 MB" style label. */
function mb_label(int $bytes): string
{
    return rtrim(rtrim(number_format($bytes / 1048576, 1), '0'), '.') . ' MB';
}

/** True when the server can write WebP (the smallest format). */
function webp_supported(): bool
{
    return function_exists('imagewebp') && (gd_info()['WebP Support'] ?? false);
}

/**
 * Largest acceptable file size for an image of w×h pixels. Quality is lowered
 * step by step until the file fits (e.g. home banner ≈ 230 KB).
 */
function image_byte_budget(int $w, int $h): int
{
    return max(40000, (int) ($w * $h * 0.12));
}

/**
 * Resize, compress and save an image file for the given space ($IMAGE_SPECS).
 * Used for new uploads and for re-optimising existing images.
 * Returns the new path, e.g. uploads/2026/11/ab12cd34ef56ab78.webp
 */
function process_image(string $srcFile, int $type, string $specName): string
{
    global $IMAGE_SPECS;
    $spec = $IMAGE_SPECS[$specName] ?? null;
    if (!$spec) {
        throw new RuntimeException('Unknown image type.');
    }
    if (!extension_loaded('gd')) {
        throw new RuntimeException('The server is missing the PHP GD extension needed to process images.');
    }

    @ini_set('memory_limit', '512M');
    @set_time_limit(120);
    $src = load_image($srcFile, $type);
    if (!$src) {
        throw new RuntimeException('The image file seems to be damaged or this format is not supported by the server.');
    }
    if ($type === IMAGETYPE_JPEG) {
        $src = fix_orientation($src, $srcFile);
    }
    if (!imageistruecolor($src)) {
        imagepalettetotruecolor($src); // GIF / 8-bit PNG: needed for good resizing
    }

    $dir = 'uploads/' . date('Y/m');
    if (!is_dir(ROOT . '/' . $dir) && !@mkdir(ROOT . '/' . $dir, 0775, true)) {
        throw new RuntimeException('The uploads folder is not writable.');
    }

    $alpha = $type !== IMAGETYPE_JPEG && has_transparency($src);
    $ext = webp_supported() ? 'webp' : ($alpha ? 'png' : 'jpg');
    $path = $dir . '/' . bin2hex(random_bytes(8)) . '.' . $ext;

    try {
        $main = resize_image($src, $spec['w'], $spec['h'], $spec['mode']);
        write_image($main, ROOT . '/' . $path, $ext, image_byte_budget($spec['w'], $spec['h']));
        if (!empty($spec['small'])) {
            [$sw, $sh] = $spec['small'];
            $small = resize_image($src, $sw, $sh, $spec['mode']);
            write_image($small, ROOT . '/' . small_variant($path), $ext, image_byte_budget($sw, $sh));
        }
    } catch (Throwable $ex) {
        @unlink(ROOT . '/' . $path);
        @unlink(ROOT . '/' . small_variant($path));
        throw $ex instanceof RuntimeException ? $ex : new RuntimeException('Could not process the image: ' . $ex->getMessage());
    } finally {
        imagedestroy($src);
    }

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

/**
 * Save an image. WebP/JPEG start at IMAGE_QUALITY and, if the file is bigger
 * than $maxBytes, are re-saved at lower quality (down to 55) until it fits.
 * GD never copies EXIF/GPS metadata, so saved files are always stripped.
 */
function write_image($img, string $file, string $ext, int $maxBytes = 0): void
{
    $flat = null;
    if ($ext === 'jpg') {
        // JPEG has no transparency: flatten onto white
        $flat = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        imageinterlace($flat, true); // progressive JPEG
    }

    $ok = false;
    try {
        if ($ext === 'png') {
            $ok = imagepng($img, $file, 9, PNG_ALL_FILTERS);
        } else {
            for ($quality = IMAGE_QUALITY; ; $quality -= 7) {
                $ok = $ext === 'webp' ? imagewebp($img, $file, $quality) : imagejpeg($flat, $file, $quality);
                clearstatcache(true, $file);
                if (!$ok || !$maxBytes || $quality <= 55 || filesize($file) <= $maxBytes) {
                    break;
                }
            }
        }
    } finally {
        if ($flat) {
            imagedestroy($flat);
        }
        imagedestroy($img);
    }
    if (!$ok || !is_file($file) || filesize($file) === 0) {
        throw new RuntimeException('Could not save the processed image (check that the uploads folder is writable).');
    }
}

/**
 * Every image the website uses, with where it comes from and how to save a
 * new path for it. Used by Admin → Images.
 */
function image_inventory(): array
{
    global $SETTINGS_FIELDS, $PAGE_FIELDS;
    $items = [];

    $settings = q('SELECT skey, svalue FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($SETTINGS_FIELDS as $fields) {
        foreach ($fields as $key => $f) {
            if ($f['type'] === 'image' && ($settings[$key] ?? '') !== '') {
                $items[] = ['where' => 'Settings – ' . $f['label'], 'edit' => 'settings.php', 'path' => $settings[$key], 'spec' => $f['spec'],
                    'save' => fn ($p) => q('UPDATE settings SET svalue = ? WHERE skey = ?', [$p, $key])];
            }
        }
    }

    foreach (q("SELECT page, bkey, value FROM page_blocks WHERE value <> ''")->fetchAll() as $r) {
        $f = page_field($r['page'], $r['bkey']);
        if ($f && $f['type'] === 'image') {
            $items[] = ['where' => ($PAGE_FIELDS[$r['page']]['title'] ?? $r['page']) . ' – ' . $f['label'], 'edit' => 'pages.php?page=' . urlencode($r['page']),
                'path' => $r['value'], 'spec' => $f['spec'],
                'save' => fn ($p) => q('UPDATE page_blocks SET value = ? WHERE page = ? AND bkey = ?', [$p, $r['page'], $r['bkey']])];
        }
    }

    foreach (q("SELECT id, title, image FROM tours WHERE image <> ''")->fetchAll() as $r) {
        $items[] = ['where' => 'Tour – ' . $r['title'] . ' (main image)', 'edit' => 'tour-edit.php?id=' . $r['id'], 'path' => $r['image'], 'spec' => 'tour',
            'save' => fn ($p) => q('UPDATE tours SET image = ? WHERE id = ?', [$p, $r['id']])];
    }
    foreach (q('SELECT g.id, g.path, g.tour_id, t.title FROM tour_images g JOIN tours t ON t.id = g.tour_id ORDER BY t.title, g.sort_order')->fetchAll() as $r) {
        $items[] = ['where' => 'Tour – ' . $r['title'] . ' (gallery)', 'edit' => 'tour-edit.php?id=' . $r['tour_id'], 'path' => $r['path'], 'spec' => 'tour',
            'save' => fn ($p) => q('UPDATE tour_images SET path = ? WHERE id = ?', [$p, $r['id']])];
    }
    foreach (q("SELECT id, name, image FROM destinations WHERE image <> ''")->fetchAll() as $r) {
        $items[] = ['where' => 'Destination – ' . $r['name'], 'edit' => 'destinations.php?edit=' . $r['id'], 'path' => $r['image'], 'spec' => 'destination',
            'save' => fn ($p) => q('UPDATE destinations SET image = ? WHERE id = ?', [$p, $r['id']])];
    }

    foreach ($items as &$item) {
        $item += image_status($item['path'], $item['spec']);
    }
    unset($item);
    return $items;
}

/**
 * Size/format details of a stored image and whether it still needs
 * optimising: ['exists', 'width', 'height', 'bytes', 'format', 'problems' => [...]].
 */
function image_status(string $path, string $specName): array
{
    global $IMAGE_SPECS;
    $spec = $IMAGE_SPECS[$specName];
    $path = image_path($path);
    $file = ROOT . '/' . $path;
    $out = ['exists' => false, 'width' => 0, 'height' => 0, 'bytes' => 0, 'format' => '', 'problems' => []];

    if (preg_match('#^(https?:)?//#i', $path)) {
        $out['problems'][] = 'External link – upload the image instead';
        return $out;
    }
    if (!image_exists($path)) {
        $out['problems'][] = 'File missing on the server';
        return $out;
    }
    $info = @getimagesize($file);
    $out['exists'] = true;
    $out['bytes'] = (int) filesize($file);
    if (!$info) {
        $out['problems'][] = 'Not a readable image';
        return $out;
    }
    [$out['width'], $out['height']] = $info;
    $out['format'] = strtoupper(image_type_to_extension($info[2], false));

    if (webp_supported() && $info[2] !== IMAGETYPE_WEBP) {
        $out['problems'][] = 'Not WebP';
    }
    if ($info[0] > $spec['w'] + 1 || $info[1] > $spec['h'] + 1) {
        $out['problems'][] = "Too big ({$info[0]}×{$info[1]}, needs at most {$spec['w']}×{$spec['h']})";
    }
    if ($out['bytes'] > image_byte_budget($spec['w'], $spec['h']) * 1.5) {
        $out['problems'][] = 'Heavy file (' . round($out['bytes'] / 1024) . ' KB)';
    }
    if (!empty($spec['small']) && !is_file(ROOT . '/' . small_variant($path))) {
        $out['problems'][] = 'No thumbnail copy';
    }
    return $out;
}

/**
 * Re-process one inventory item, save the new path in the database and
 * delete the old file (only files this CMS created – hand-uploaded files
 * such as uploads/logo.jpg are left in place).
 */
function optimise_inventory_item(array $item): string
{
    $file = ROOT . '/' . image_path($item['path']);
    $info = @getimagesize($file);
    if (!$info) {
        throw new RuntimeException('Not a readable image.');
    }
    $new = process_image($file, $info[2], $item['spec']);
    ($item['save'])($new);
    if (preg_match('#^uploads/\d{4}/\d{2}/[0-9a-f]{16}\.(webp|jpg|png)$#', image_path($item['path']))) {
        delete_image(image_path($item['path']));
    }
    return $new;
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
