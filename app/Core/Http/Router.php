<?php

namespace App\Core\Http;

use App\Core\Exceptions\UrlException; // UrlException'ı import et

class Router
{
    /**
     * @var array Kayıtlı rotalar
     */
    protected array $routes = [];

    /**
     * Yeni bir GET rotası tanımlar.
     *
     * @param string $uri URL kalıbı (örn: '/users/{id}')
     * @param mixed $action Rotanın aksiyonu (string 'Controller@method' veya Closure)
     */
    public function get(string $uri, $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    /**
     * Yeni bir POST rotası tanımlar.
     *
     * @param string $uri URL kalıbı
     * @param mixed $action Rotanın aksiyonu
     */
    public function post(string $uri, $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    /**
     * Yeni bir PUT rotası tanımlar.
     *
     * @param string $uri URL kalıbı
     * @param mixed $action Rotanın aksiyonu
     */
    public function put(string $uri, $action): void
    {
        $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Yeni bir DELETE rotası tanımlar.
     *
     * @param string $uri URL kalıbı
     * @param mixed $action Rotanın aksiyonu
     */
    public function delete(string $uri, $action): void
    {
        $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Tüm HTTP metotları için rota tanımlar.
     *
     * @param string $uri URL kalıbı
     * @param mixed $action Rotanın aksiyonu
     */
    public function any(string $uri, $action): void
    {
        $this->addRoute(['GET', 'POST', 'PUT', 'DELETE'], $uri, $action);
    }

    /**
     * Belirli bir metot ve URI için rota ekler.
     *
     * @param string|array $methods HTTP metodu(ları)
     * @param string $uri URL kalıbı
     * @param mixed $action Rotanın aksiyonu
     */
    protected function addRoute($methods, string $uri, $action): void
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }

        // URI'yi regex'e dönüştür ve parametre adlarını kaydet
        // Örnek: /users/{id} -> #^/users/(?P<id>[^/]+)$#
        $uriRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $uri);
        $uriRegex = '#^' . str_replace('/', '\/', $uriRegex) . '$#';

        foreach ($methods as $method) {
            $this->routes[$method][] = [
                'uri' => $uri,        // Orijinal URI kalıbı
                'regex' => $uriRegex, // Eşleştirme için Regex
                'action' => $action,  // Çağrılacak aksiyon
                'params' => $this->extractParamsFromUri($uri) // Parametre isimlerini kaydet
            ];
        }
    }

    /**
     * URI kalıbından parametre isimlerini çıkarır.
     *
     * @param string $uri
     * @return array
     */
    protected function extractParamsFromUri(string $uri): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $uri, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Gelen isteği rotalarla eşleştirir ve eşleşen aksiyonu döndürür.
     *
     * @param Request $request Gelen HTTP isteği
     * @return array|null Eşleşen rota bilgisi (action, params) veya null
     * @throws UrlException Eğer rota bulunamazsa
     */
    public function dispatch(Request $request): ?array
    {
        $requestUri = rtrim($request->uri(), '/'); // Sondaki slash'ı kaldır
        $requestUri = $requestUri === '' ? '/' : $requestUri; // Eğer boşsa kök dizin olarak ayarla
        $requestMethod = $request->method();

        if (!isset($this->routes[$requestMethod])) {
            // Belirtilen metot için hiç rota yoksa
            throw new UrlException("Method Not Allowed: " . $requestMethod, 405);
        }

        foreach ($this->routes[$requestMethod] as $route) {
            if (preg_match($route['regex'], $requestUri, $matches)) {
                $params = [];
                foreach ($route['params'] as $paramName) {
                    if (isset($matches[$paramName])) {
                        $params[$paramName] = $matches[$paramName];
                    }
                }
                return [
                    'action' => $route['action'],
                    'params' => $params
                ];
            }
        }

        // Hiçbir rota eşleşmedi
        throw new UrlException("Not Found: " . $requestUri, 404);
    }

    /**
     * Harici olarak rotaları tanımlamak için kullanılır.
     *
     * @param callable $callback Rota tanımlama Closure'ı
     */
    public function registerRoutes(callable $callback): void
    {
        $callback($this);
    }
}