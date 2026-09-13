<?php
if (!function_exists('clean_input')) {
    function clean_input(mixed $value): mixed {
        if (is_array($value)) return array_map('clean_input', $value);
        return htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
    }
}
