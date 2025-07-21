<?php

namespace App\Core\Http;

use App\Core\Config;

class Request
{
    protected array $get;
    protected array $post;
    protected array $server;
    protected array $files;
    protected array $headers = [];
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->parseHeaders();
    }

    protected function parseHeaders(): void
    {
        if (function_exists('getallheaders')) {
            $this->headers = getallheaders();
        } else {
            foreach ($this->server as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $this->headers[$headerName] = $value;
                }
            }
        }
    }

    /**
     * İsteğin URI'sini döndürür, uygulama temel URL'sini dikkate alarak.
     * Bu metod, URL'deki uygulama alt dizinini (örn. /lovesta-framework) kaldırır.
     *
     * @return string
     */
    public function uri(): string
    {
        $requestUri = strtok($this->server['REQUEST_URI'] ?? '/', '?'); // Query string'i kaldır

        $baseUrl = $this->config->get('app.url', '/');
        $basePath = parse_url($baseUrl, PHP_URL_PATH); // Sadece yolu al: /lovesta-framework

        // Temel yolu ve istek URI'sini sondaki eğik çizgiden arındırarak tutarlı hale getir
        $basePath = rtrim($basePath, '/');
        $requestUri = rtrim($requestUri, '/');

        // Eğer istek URI'si tam olarak temel yola eşitse (örn. /lovesta-framework),
        // bunu uygulamanın kök rotası '/' olarak kabul et.
        if ($requestUri === $basePath) {
            return '/';
        }

        // Eğer istek URI'si temel yolla başlıyorsa (örn. /lovesta-framework/login),
        // temel yolu URI'den kaldır.
        if (str_starts_with($requestUri, $basePath)) {
            $requestUri = substr($requestUri, strlen($basePath));
        }

        // URI'nin daima bir önde eğik çizgiyle başladığından emin ol
        // ve boş URI'yi '/' olarak normalleştir.
        $requestUri = '/' . ltrim($requestUri, '/');
        if ($requestUri === '') {
            $requestUri = '/'; // Bu durum, örneğin /lovesta-framework/home/ gibi bir URL'den /lovesta-framework/home gelirse oluşabilir.
        }

        return $requestUri;
    }

    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function input(string $key, $default = null)
    {
        return $this->all()[$key] ?? $default;
    }

    public function query(string $key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    public function header(string $key, $default = null)
    {
        $normalizedKey = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $key))));
        return $this->headers[$normalizedKey] ?? $default;
    }

    public function isAjax(): bool
    {
        return ($this->header('X-Requested-With') === 'XMLHttpRequest');
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function ip(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }
}