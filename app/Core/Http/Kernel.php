<?php

namespace App\Core\Http;

use App\Core\Application;
use App\Core\Exceptions\UrlException;
use App\Core\Exceptions\Handler as ExceptionHandler;
use Exception;
use Throwable;
use Psr\Container\ContainerInterface;

class Kernel
{
    protected Application $app;
    protected Router $router;
    protected array $middleware = [];

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    public function handle(Request $request): Response
    {
        try {
            $this->loadRoutes();
            $routeInfo = $this->router->dispatch($request);

            if ($routeInfo === null) {
                throw new UrlException("No route matched for URI: " . $request->uri(), 404);
            }

            $action = $routeInfo['action'];
            $params = $routeInfo['params'];

            $response = $this->callAction($action, $params, $request);

        } catch (UrlException $e) {
            if ($e->getCode() === 404) {
                return $this->handleNotFound($request, $e);
            } elseif ($e->getCode() === 405) {
                return new Response('Method Not Allowed: ' . $request->method(), 405);
            }
            return new Response($e->getMessage(), $e->getCode());
        } catch (Throwable $e) {
            $handler = $this->app->getContainer()->get(ExceptionHandler::class);
            return $handler->handle($e, $request);
        }

        return $response;
    }

    protected function loadRoutes(): void
    {
        $container = $this->app->getContainer();
        $router = $this->router;

        $routeFiles = [
            BASE_PATH . '/routes/web.php',
            BASE_PATH . '/routes/api.php',
        ];

        foreach ($routeFiles as $file) {
            if (file_exists($file)) {
                (require $file)($router, $container);
            }
        }
    }

    /**
     * Eşleşen aksiyonu çağırır ve bir Response döndürür.
     * Bu metodun dönüş değeri kesinlikle App\Core\Http\Response olmalıdır.
     *
     * @param mixed $action Controller@method stringi, Closure veya [ControllerClass::class, 'methodName'] dizisi
     * @param array $params URL parametreleri
     * @param Request $request İstek nesnesi
     * @return Response
     * @throws Exception Eğer aksiyon çağrılamazsa
     */
    protected function callAction($action, array $params, Request $request): Response
    {
        $actionResult = null; // Aksiyonun döndürdüğü ham sonucu tutar

        // Eğer aksiyon [ControllerClass::class, 'methodName'] formatında bir dizi ise
        // Bu kontrolü en başa alıyoruz çünkü is_callable() non-statik metodlar için false dönebilir.
        if (is_array($action) && count($action) === 2 && is_string($action[0]) && is_string($action[1])) {
            [$controllerClass, $methodName] = $action;
            
            // PHP-DI'nın call metodu, sınıfı otomatik olarak çözüp metodu çağırabilir.
            $actionResult = $this->app->getContainer()->call([$controllerClass, $methodName], array_merge(['request' => $request], $params));
        }
        // Eğer aksiyon bir Closure (anonim fonksiyon) ise (ki bu da is_callable'dir)
        elseif (is_callable($action)) {
            $actionResult = $this->app->getContainer()->call($action, array_merge(['request' => $request], $params));
        }
        // Eğer aksiyon "Controller@method" string formatında ise
        elseif (is_string($action) && str_contains($action, '@')) {
            [$controllerClass, $methodName] = explode('@', $action);
            $actionResult = $this->app->getContainer()->call([$controllerClass, $methodName], array_merge(['request' => $request], $params));
        }
        // Hiçbir geçerli aksiyon formatına uymuyorsa hata fırlat
        else {
            throw new Exception("Invalid route action: Unknown action type.");
        }

        // Eğer aksiyonun sonucu zaten bir Response objesiyse, doğrudan döndür.
        if ($actionResult instanceof Response) {
            return $actionResult;
        }

        // Eğer sonuç bir dizi veya obje ise, JSON Response olarak dönüştür.
        if (is_array($actionResult) || is_object($actionResult)) {
            return Response::json((array) $actionResult);
        }

        // Diğer durumlarda (örn. string, int, null vb.), Plain Text Response olarak dönüştür.
        return new Response((string) $actionResult);
    }

    protected function handleNotFound(Request $request, UrlException $e): Response
    {
        if (APP_DEBUG) {
            $message = "<h1>Lovesta Framework - Debug Mode (404 Not Found)</h1>";
            $message .= "<p>No route matched for: " . htmlspecialchars($request->uri()) . "</p>";
            $message .= "<p>This indicates that the requested URL does not correspond to any defined route or active plugin.</p>";
            return new Response($message, 404);
        } else {
            try {
                return Response::view('resources/views/404.php', [], 404);
            } catch (Throwable $viewException) {
                error_log("404 view could not be rendered: " . $viewException->getMessage());
                return new Response("<h1>404 Not Found</h1><p>The page you requested could not be found.</p>", 404);
            }
        }
    }
}