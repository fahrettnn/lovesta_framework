<?php

// routes/api.php

use App\Core\Http\Router;
use App\Core\Http\Response;
use App\Core\Http\Request;
use Psr\Container\ContainerInterface;

/**
 * API rotalarını tanımlar.
 * @param Router $router Router nesnesi
 * @param ContainerInterface $container IoC Konteyner
 */
return function (Router $router, ContainerInterface $container) {
    $router->get('/api/v1/posts', function () {
        return Response::json([
            ['id' => 1, 'title' => 'First API Post', 'content' => 'Lorem ipsum'],
            ['id' => 2, 'title' => 'Second API Post', 'content' => 'Dolor sit amet'],
        ]);
    });

    $router->post('/api/v1/posts', function (Request $request) {
        $data = $request->all();
        return Response::json(['message' => 'API Post created successfully!', 'received_data' => $data], 201);
    });

    // Örnek: API için de doğrudan kontrolcü kullanımı yerine Closure veya plugin içi çağrı.
    // Bu da geçici bir test rotasıdır.
    $router->get('/api/test-user/{id}', function (string $id) {
        return Response::json(['api_user_id' => $id, 'status' => 'success']);
    });
};