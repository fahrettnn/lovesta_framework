<?php

// Framework'ün kök dizinini tanımla
if (!defined('APP_ROOT_PATH')) {
    define('APP_ROOT_PATH', dirname(__DIR__));
}
define('BASE_PATH', APP_ROOT_PATH);

require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/app/Core/init.php';

use Nette\Loaders\RobotLoader;
use Nette\Caching\Storages\FileStorage;
use App\Core\Application;
use App\Core\Config;
use Dotenv\Dotenv;
use Tracy\Debugger;
use Tracy\BlueScreen;
use App\Console\Commands\MakePluginCommand; // make:plugin komutunu import et

// Ortam değişkenlerini yükle
$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

define('APP_DEBUG', env('APP_DEBUG', false));

if (APP_DEBUG) {
    Debugger::enable(Debugger::DEVELOPMENT);
} else {
    Debugger::enable(Debugger::PRODUCTION, BASE_PATH . '/storage/logs');
}

// Nette RobotLoader konfigürasyonu
$robotLoader = new RobotLoader();
$robotLoader->addDirectory(BASE_PATH . '/app'); // Uygulama çekirdek dizinini tara

// DİNAMİK OLARAK PLUGIN DİZİNLERİNİ ROBOTLOADER'A addDirectory İLE EKLEME
$pluginsDir = BASE_PATH . '/plugins';

if (is_dir($pluginsDir)) {
    $pluginSlugs = glob($pluginsDir . '/*', GLOB_ONLYDIR);
    foreach ($pluginSlugs as $pluginPath) {
        $robotLoader->addDirectory($pluginPath); // Plugin'in ana dizinini ekle
        
        // Plugin'in içindeki yaygın alt dizinleri de ekle
        $subdirsToScan = ['controllers', 'models', 'views', 'migrations', 'services'];
        foreach ($subdirsToScan as $subdir) {
            $dir = $pluginPath . '/' . $subdir;
            if (is_dir($dir)) {
                $robotLoader->addDirectory($dir);
            }
        }
    }
}

// Ana uygulama migrationları için de ekle (eğer kullanılıyorsa)
$databaseMigrationsPath = BASE_PATH . '/database/migrations';
if (is_dir($databaseMigrationsPath)) {
    $robotLoader->addDirectory($databaseMigrationsPath);
}

$robotLoader->setTempDirectory(BASE_PATH . '/storage/framework/cache');
$robotLoader->register();

// Konfigürasyon sınıfını başlat
$config = new Config();
$configFiles = glob(BASE_PATH . '/config/*.php');
foreach ($configFiles as $configFile) {
    $config->load(basename($configFile, '.php'), require $configFile);
}

// Uygulama örneğini oluştur
$app = new Application($config);
$container = $app->getContainer();
$container->set(Application::class, $app);

// Eklentileri yükle
$app->bootPlugins();

return $app;