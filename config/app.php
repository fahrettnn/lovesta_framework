<?php
// C:\xampp\htdocs\lovesta-framework\config\app.php

// Her zaman bir dizi (array) döndürdüğünden emin olun
return [
    'name' => env('APP_NAME', 'Lovesta Framework'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost/lovesta-framework'), // URL'yi kendi kurduğunuza göre güncelledim
    'timezone' => 'Europe/Istanbul',
    'locale' => 'tr',
    'fallback_locale' => 'en',
    // ... diğer ayarlar ...
];