<?php

namespace Head;

use App\Core\Http\Router;
use App\Core\Helpers\ActionFilterHelper;
use Psr\Container\ContainerInterface;
// use Head\\Controllers\\ExampleController;

use function DI\autowire;

if (!defined('HEAD_PLUGIN_PATH')) {
    define('HEAD_PLUGIN_PATH', __DIR__);
}

return function (Router $router, ContainerInterface $container, ActionFilterHelper $actionFilter) {
    /** @var \DI\Container \$container */

    // Örnek servis kaydı:
    // \$container->set(HeadController::class, autowire(HeadController::class));

    // Örnek rota tanımı:
    // \$router->get('/head', [HeadController::class, 'index']);

    // Örnek aksiyon/filtre kaydı:
    // \$actionFilter->addAction('app_init', function() {
    //     \$container->get(Psr\\Log\\LoggerInterface::class)->info("Plugin 'head' initialized!");
    // });
};