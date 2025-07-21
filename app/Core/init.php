<?php
// app/Core/init.php

// Ortam değişkenine erişim sağlayan global bir helper fonksiyonu (vlucas/phpdotenv için)
if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}