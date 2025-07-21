<?php

// Framework'ün kök dizinini tanımla.
// Bu, public dizininin bir üst dizini olacaktır: C:\xampp\htdocs\lovesta-framework
define('APP_ROOT_PATH', __DIR__);
$app = require_once APP_ROOT_PATH . '/bootstrap/app.php';

global $app; // BU SATIRI EKLEYİN

use App\Core\Http\Request;
use App\Core\Http\Kernel;

// IoC Konteynerden Request ve Kernel nesnelerini al
$container = $app->getContainer();

// Request nesnesini otomatik olarak oluştur
$request = $container->get(Request::class);

// Kernel nesnesini otomatik olarak oluştur
$kernel = $container->get(Kernel::class);

// İsteği Kernel aracılığıyla işle
$response = $kernel->handle($request);

// Yanıtı istemciye gönder
$response->send();