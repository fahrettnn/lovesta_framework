<?php

namespace Header;

use App\Core\Http\Router;
use App\Core\Helpers\ActionFilterHelper;
use Psr\Container\ContainerInterface;
// use Header\\Controllers\\ExampleController;

use function DI\autowire;

if (!defined('HEADER_PLUGIN_PATH')) {
    define('HEADER_PLUGIN_PATH', __DIR__);
}

return function (Router $router, ContainerInterface $container, ActionFilterHelper $actionFilter) {
    /** @var \DI\Container \$container */

    // Örnek servis kaydı:
    // \$container->set(HeaderController::class, autowire(HeaderController::class));

    // Örnek rota tanımı:
    // \$router->get('/header', [HeaderController::class, 'index']);

    // Örnek aksiyon/filtre kaydı:
    // \$actionFilter->addAction('app_init', function() {
    //     \$container->get(Psr\\Log\\LoggerInterface::class)->info("Plugin 'header' initialized!");
    // });
};