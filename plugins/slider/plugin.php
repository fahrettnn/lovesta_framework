<?php

namespace Slider;

use App\Core\Http\Router;
use App\Core\Helpers\ActionFilterHelper;
use App\Core\Http\Response;
use Psr\Container\ContainerInterface;
use Slider\Controllers\SliderController;

use function DI\autowire;

if (!defined('SLIDER_PLUGIN_PATH')) {
    define('SLIDER_PLUGIN_PATH', __DIR__);
}

return function (Router $router, ContainerInterface $container, ActionFilterHelper $actionFilter) {
    /** @var \DI\Container \$container */

    $actionFilter->addAction('top_page_slider', function() {
        Response::renderPartial('slider::main_slider'); // 'pluginAdi::viewAdi' formatını kullanabilirsiniz
    });
    $router->get('/slider', [SliderController::class, 'index']);
};