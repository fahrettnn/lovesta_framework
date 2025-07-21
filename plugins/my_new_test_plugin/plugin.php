<?php

namespace MyNewTestPlugin;

use App\Core\Http\Router;
use App\Core\Helpers\ActionFilterHelper;
use Psr\Container\ContainerInterface;
// use MyNewTestPlugin\\Controllers\\ExampleController;

use function DI\autowire;

if (!defined('MY_NEW_TEST_PLUGIN_PLUGIN_PATH')) {
    define('MY_NEW_TEST_PLUGIN_PLUGIN_PATH', __DIR__);
}

return function (Router $router, ContainerInterface $container, ActionFilterHelper $actionFilter) {
    /** @var \DI\Container \$container */

    // Örnek servis kaydı:
    // \$container->set(MynewtestpluginController::class, autowire(MynewtestpluginController::class));

    // Örnek rota tanımı:
    // \$router->get('/my_new_test_plugin', [MynewtestpluginController::class, 'index']);

    // Örnek aksiyon/filtre kaydı:
    // \$actionFilter->addAction('app_init', function() {
    //     \$container->get(Psr\\Log\\LoggerInterface::class)->info("Plugin 'my_new_test_plugin' initialized!");
    // });
};