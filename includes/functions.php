<?php

/** Escape a value for HTML output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Format a price, e.g. "USD 1,290". */
function price($amount): string
{
    return setting('currency') . ' ' . number_format((int) $amount);
}

/** Turn plain text with blank lines into <p> paragraphs. */
function paragraphs(string $text): string
{
    $out = '';
    foreach (preg_split('/\R{2,}/', trim($text)) as $para) {
        if ($para !== '') {
            $out .= '<p>' . nl2br(e($para)) . '</p>';
        }
    }
    return $out;
}

/** Make a URL slug from a title. */
function slugify(string $text): string
{
    $text = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $text !== '' ? $text : 'item';
}

/** CSRF token for forms. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/** "active" class for the current front-end nav item. */
function nav_active(string $page): string
{
    global $currentPage;
    return ($currentPage ?? '') === $page ? ' class="active"' : '';
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** One-time messages shown on the next page (admin). */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Split a textarea into non-empty trimmed lines. */
function lines(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $text)), 'strlen'));
}

/** Lines of "a | b" into [[a, b], ...]. */
function pair_lines(string $text): array
{
    $out = [];
    foreach (lines($text) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        $out[] = [$parts[0], $parts[1] ?? ''];
    }
    return $out;
}

/** [[a, b], ...] back to "a | b" lines for editing. */
function pairs_to_text(array $pairs): string
{
    return implode("\n", array_map(fn ($p) => $p[0] . ' | ' . ($p[1] ?? ''), $pairs));
}
