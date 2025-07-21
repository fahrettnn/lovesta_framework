<?php

// routes/web.php

use App\Core\Http\Router;
use App\Core\Http\Response;
use App\Core\Http\Request; // Request nesnesini kullanmak için
use Psr\Container\ContainerInterface; // PHP-DI container interface'i

/**
 * Web rotalarını tanımlar.
 * @param Router $router Router nesnesi
 * @param ContainerInterface $container IoC Konteyner
 */
return function (Router $router, ContainerInterface $container) {
    /**
     * $router->get('/about', function () {
        return new Response("This is the About Us page.");
    });
     */

    // Örnek: Plugin kontrolcüsünü burada doğrudan çağırmak yerine,
    // PluginLoader aracılığıyla yüklenmesini sağlayacağız.
    // Şimdilik test amaçlı basit bir kullanıcı rotası bırakalım,
    // ancak bu rota daha sonra ilgili plugin'in kendi `plugin.php` dosyasına taşınacak.
    // Örneğin, 'plugins/auth_profile_edit' plugin'inin içinde bir kontrolcü varsayalım.
    // RobotLoader sayesinde tam namespace'i ile erişilebilir olmalı.
    // Önemli Not: Bu sadece geçici bir test rotasıdır.
    // Plugin sistemi devreye girdiğinde bu tür rotalar plugin'in kendi içinde tanımlanacaktır.
    /**
     * $router->get('/user/{id}', function (Request $request, string $id) {
        $user = ['id' => $id, 'name' => 'Plugin User ' . $id, 'email' => 'user' . $id . '@plugin.com'];
        return Response::json($user);
    });
     */
};