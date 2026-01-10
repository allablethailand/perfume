<?php
if (!function_exists('t')) {
    function t(string $key, array $translations, string $lang = 'en'): string
    {
        return $translations[$key][$lang]
            ?? $translations[$key]['en']
            ?? $key;
    }
}
?>