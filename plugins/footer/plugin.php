<?php

namespace Footer; // Namespace'in Footer olduğundan emin olun

use App\Core\Http\Router;
use App\Core\Helpers\ActionFilterHelper;
use App\Core\Http\Response; // Response sınıfını kullanmak için
use Psr\Container\ContainerInterface;

// use Footer\Controllers\ExampleController; // Eğer bir Controller'ınız varsa

use function DI\autowire;

if (!defined('FOOTER_PLUGIN_PATH')) {
    define('FOOTER_PLUGIN_PATH', __DIR__);
}

return function (Router $router, ContainerInterface $container, ActionFilterHelper $actionFilter) {
    /** @var \DI\Container $container */

    // app_footer_content aksiyonuna kendi callback'imizi ekliyoruz
    $actionFilter->addAction('app_footer_content', function() {
        // Footer eklentisinin view'ını doğrudan çıktıya render ediyoruz.
        // renderPartial metodunu kullanabiliriz, çünkü bu doğrudan çıktıyı basar.
        Response::renderPartial('footer::footer'); // 'footer::footer' formatını kullanın
    });

    // Eğer footer eklentinizin kendine ait rotaları veya servisleri varsa buraya ekleyebilirsiniz.
    // $router->get('/footer-info', [FooterController::class, 'info']);
    // $container->set(FooterService::class, autowire(FooterService::class));
};