<?php

namespace Home;

use App\Core\Http\Router;
use App\Core\Helpers\ActionFilterHelper;
use Psr\Container\ContainerInterface;
use Home\Controllers\HomeController; // <-- BU SATIRI EKLEYİN VEYA YORUMDAN ÇIKARIN

use function DI\autowire;

if (!defined('HOME_PLUGIN_PATH')) {
    define('HOME_PLUGIN_PATH', __DIR__);
}

return function (Router $router, ContainerInterface $container, ActionFilterHelper $actionFilter) {
    /** @var \DI\Container \$container */

    // Örnek servis kaydı:
    // \$container->set(HomeController::class, autowire(HomeController::class));

    // Örnek rota tanımı:
    $router->get('/', [HomeController::class, 'index']);

    // Örnek aksiyon/filtre kaydı:
    $actionFilter->addAction('app_init', function() {
        echo "Home Plugin app_init";
    });
};