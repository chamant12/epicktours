<?php
/** Form field helpers for the CMS. */

/** Render a CMS field (text, textarea or image) as a Bootstrap form group. */
function cms_field(string $name, array $f, string $value): string
{
    $label = e($f['label']);
    $help = !empty($f['help']) ? '<small class="form-text text-muted">' . e($f['help']) . '</small>' : '';
    $id = 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
    // Live character count with a recommended maximum (SEO fields)
    $counter = !empty($f['counter']) ? ' data-counter="' . (int) $f['counter'] . '"' : '';

    switch ($f['type']) {
        case 'textarea':
            $rows = (int) ($f['rows'] ?? 3);
            return "<div class=\"form-group\"><label for=\"{$id}\">{$label}</label><textarea class=\"form-control\" id=\"{$id}\" name=\"" . e($name) . "\" rows=\"{$rows}\"{$counter}>" . e($value) . "</textarea>{$help}</div>";
        case 'image':
            return image_field($name, $f['label'], $value, $f['spec'], $f['help'] ?? '');
        case 'select':
            $opts = '';
            foreach ($f['options'] as $optValue => $optLabel) {
                $opts .= '<option value="' . e($optValue) . '"' . ((string) $optValue === $value ? ' selected' : '') . '>' . e($optLabel) . '</option>';
            }
            return "<div class=\"form-group\"><label for=\"{$id}\">{$label}</label><select class=\"form-control\" id=\"{$id}\" name=\"" . e($name) . "\">{$opts}</select>{$help}</div>";
        case 'password':
            // Never sent back to the browser; empty keeps the saved value
            $placeholder = $value !== '' ? '•••••••• (saved)' : '';
            return "<div class=\"form-group\"><label for=\"{$id}\">{$label}</label><input type=\"password\" class=\"form-control\" id=\"{$id}\" name=\"" . e($name) . "\" placeholder=\"{$placeholder}\" autocomplete=\"new-password\">{$help}</div>";
        default:
            return "<div class=\"form-group\"><label for=\"{$id}\">{$label}</label><input type=\"text\" class=\"form-control\" id=\"{$id}\" name=\"" . e($name) . "\" value=\"" . e($value) . "\"{$counter}>{$help}</div>";
    }
}

/** Image upload with preview, replace and remove. */
function image_field(string $name, string $label, string $value, string $spec, string $help = ''): string
{
    $has = image_exists($value);
    $preview = $has
        ? '<img src="' . e(img_url($value)) . '" class="img-preview" alt="">'
        : '<div class="img-preview img-empty"><i class="fas fa-image"></i><span>No image</span></div>';
    $remove = $has
        ? '<div class="custom-control custom-checkbox mt-2"><input type="checkbox" class="custom-control-input" id="rm_' . e($name) . '" name="' . e($name) . '_remove" value="1"><label class="custom-control-label" for="rm_' . e($name) . '">Remove this image</label></div>'
        : '';
    return '<div class="form-group image-field">'
        . '<label>' . e($label) . '</label>'
        . '<div class="d-flex flex-wrap align-items-start">'
        . '<div class="mr-3 mb-2">' . $preview . '</div>'
        . '<div class="flex-grow-1" style="min-width:220px">'
        . '<div class="custom-file"><input type="file" class="custom-file-input" id="up_' . e($name) . '" name="' . e($name) . '" accept="image/jpeg,image/png,image/webp,image/gif"><label class="custom-file-label" for="up_' . e($name) . '">' . ($has ? 'Replace image…' : 'Upload image…') . '</label></div>'
        . '<small class="form-text text-muted"><i class="fas fa-magic"></i> Resized to ' . e(spec_label($spec)) . ' and optimised automatically. JPG, PNG, GIF or WebP up to ' . round(MAX_UPLOAD_BYTES / 1048576) . ' MB.' . ($help ? ' ' . e($help) : '') . '</small>'
        . $remove
        . '</div></div></div>';
}

/** Checkbox switch. */
function switch_field(string $name, string $label, bool $checked): string
{
    $id = 'sw_' . e($name);
    return '<div class="custom-control custom-switch mb-3"><input type="hidden" name="' . e($name) . '" value="0"><input type="checkbox" class="custom-control-input" id="' . $id . '" name="' . e($name) . '" value="1"' . ($checked ? ' checked' : '') . '><label class="custom-control-label" for="' . $id . '">' . e($label) . '</label></div>';
}

/** A small POST form button (delete, toggle…) with CSRF and optional confirm. */
function post_button(string $action, array $fields, string $label, string $class, string $confirm = ''): string
{
    $html = '<form method="post" action="' . e($action) . '" class="d-inline"' . ($confirm ? ' onsubmit="return confirm(\'' . e(addslashes($confirm)) . '\')"' : '') . '>' . csrf_field();
    foreach ($fields as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $html . '<button class="btn btn-sm ' . e($class) . '">' . $label . '</button></form>';
}

function status_badge(bool $active): string
{
    return $active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Hidden</span>';
}
