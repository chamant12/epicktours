<?php
/** Read site content from the database. */

/** A site setting, falling back to its default in cms-fields.php. */
function setting(string $key): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = q('SELECT skey, svalue FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    if (isset($cache[$key]) && $cache[$key] !== '') {
        return $cache[$key];
    }
    return setting_field($key)['default'] ?? '';
}

/** Editable page text/image, falling back to its default. */
function block(string $page, string $key): string
{
    static $cache = [];
    if (!isset($cache[$page])) {
        $cache[$page] = q('SELECT bkey, value FROM page_blocks WHERE page = ?', [$page])->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    $value = $cache[$page][$key] ?? null;
    if ($value !== null && $value !== '') {
        return $value;
    }
    return page_field($page, $key)['default'] ?? '';
}

/** Escaped block with line breaks kept. */
function block_br(string $page, string $key): string
{
    return nl2br(e(block($page, $key)));
}

function tour_categories(): array
{
    return lines(setting('tour_categories'));
}

/** Decode the list columns of a tour row. */
function decode_tour(array $row): array
{
    foreach (['highlights', 'itinerary', 'inclusions', 'exclusions', 'hotels', 'destinations'] as $col) {
        $row[$col] = json_decode($row[$col] ?? '[]', true) ?: [];
    }
    return $row;
}

/** Active tours, optionally filtered by category or destination slug. */
function get_tours(array $filter = []): array
{
    $sql = 'SELECT * FROM tours WHERE is_active = 1';
    $params = [];
    if (!empty($filter['category'])) {
        $sql .= ' AND category = ?';
        $params[] = $filter['category'];
    }
    if (!empty($filter['featured'])) {
        $sql .= ' AND is_featured = 1';
    }
    $sql .= ' ORDER BY sort_order, id';
    $tours = array_map('decode_tour', q($sql, $params)->fetchAll());

    if (!empty($filter['destination'])) {
        $tours = array_values(array_filter($tours, fn ($t) => in_array($filter['destination'], $t['destinations'], true)));
    }
    return $tours;
}

function find_tour(string $slug, bool $activeOnly = true): ?array
{
    $row = q('SELECT * FROM tours WHERE slug = ?' . ($activeOnly ? ' AND is_active = 1' : ''), [$slug])->fetch();
    return $row ? decode_tour($row) : null;
}

/** Main image followed by the extra gallery images. */
function tour_gallery(array $tour): array
{
    $images = $tour['image'] ? [$tour['image']] : [];
    foreach (q('SELECT path FROM tour_images WHERE tour_id = ? ORDER BY sort_order, id', [$tour['id']])->fetchAll(PDO::FETCH_COLUMN) as $p) {
        $images[] = $p;
    }
    return $images ?: [''];
}

/** Active destinations keyed by slug. */
function get_destinations(bool $activeOnly = true): array
{
    $rows = q('SELECT * FROM destinations' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order, id')->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $out[$r['slug']] = $r;
    }
    return $out;
}

function get_experiences(): array
{
    return q('SELECT * FROM experiences WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
}

function get_themes(): array
{
    return q('SELECT * FROM themes WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
}
