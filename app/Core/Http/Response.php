<?php

namespace App\Core\Http;

class Response
{
    /**
     * @var string Yanıt içeriği
     */
    protected string $content;

    /**
     * @var int HTTP durum kodu
     */
    protected int $statusCode;

    /**
     * @var array Yanıt başlıkları
     */
    protected array $headers = [];

    /**
     * HTTP durum kodları eşlemesi
     */
    protected const STATUS_TEXTS = [
        100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 103 => 'Early Hints',
        200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information',
        204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status',
        208 => 'Already Reported', 226 => 'IM Used',
        300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other',
        304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
        400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden',
        404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required',
        408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required',
        412 => 'Precondition Failed', 413 => 'Payload Too Large', 414 => 'URI Too Long', 415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable', 417 => 'Expectation Failed', 418 => 'I\'m a teapot',
        421 => 'Misdirected Request', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency',
        425 => 'Too Early', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large', 451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable',
        504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage', 508 => 'Loop Detected', 510 => 'Not Extended', 511 => 'Network Authentication Required',
    ];

    /**
     * Response sınıfının yapıcı metodu.
     *
     * @param string $content Yanıt içeriği
     * @param int $statusCode HTTP durum kodu
     * @param array $headers Yanıt başlıkları
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Yanıt içeriğini ayarlar.
     *
     * @param string $content Yanıt içeriği
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Yanıt içeriğini döndürür.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * HTTP durum kodunu ayarlar.
     *
     * @param int $statusCode HTTP durum kodu
     * @return self
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * HTTP durum kodunu döndürür.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Belirli bir başlığı ayarlar.
     *
     * @param string $key Başlık anahtarı
     * @param string $value Başlık değeri
     * @return self
     */
    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Tüm başlıkları ayarlar.
     *
     * @param array $headers Başlık dizisi
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Belirli bir başlığı döndürür.
     *
     * @param string $key Başlık anahtarı
     * @return string|null
     */
    public function getHeader(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    /**
     * Tüm başlıkları döndürür.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Yanıtı istemciye gönderir.
     */
    public function send(): void
    {
        // HTTP durum başlığını gönder
        if (headers_sent() === false) {
            $statusText = self::STATUS_TEXTS[$this->statusCode] ?? 'Unknown Status';
            header(sprintf('HTTP/%s %s %s', '1.1', $this->statusCode, $statusText), true, $this->statusCode);

            // Diğer başlıkları gönder
            foreach ($this->headers as $key => $value) {
                header(sprintf('%s: %s', $key, $value), false);
            }
        }

        // İçeriği yazdır
        echo $this->content;
    }

    /**
     * JSON yanıtı oluşturur ve gönderir.
     *
     * @param array $data JSON'a dönüştürülecek veri
     * @param int $statusCode HTTP durum kodu
     * @param array $headers Ek başlıklar
     * @return self
     */
    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        $response = new self(json_encode($data), $statusCode);
        $response->setHeader('Content-Type', 'application/json');
        $response->setHeaders($headers);
        return $response;
    }

    /**
     * Yönlendirme (Redirect) yanıtı oluşturur.
     *
     * @param string $url Yönlendirilecek URL
     * @param int $statusCode Yönlendirme durum kodu (301, 302 vb.)
     * @return self
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        $response = new self('', $statusCode);
        $response->setHeader('Location', $url);
        return $response;
    }

    /**
     * Bir görünüm dosyasını render ederek Response oluşturur.
     *
     * @param string $viewPath Görünüm dosyasının yolu (örn: 'auth_login/views/view.php' veya 'resources/views/404.php')
     * @param array $data Görünüme gönderilecek veriler
     * @param int $statusCode HTTP durum kodu
     * @param array $headers Ek başlıklar
     * @return self
     */
    public static function view(string $viewPath, array $data = [], int $statusCode = 200, array $headers = []): self
    {
        ob_start();

        extract($data); // Verileri görünümde kullanılabilir hale getir

        $fullPath = '';
        if (str_starts_with($viewPath, 'resources/views/')) {
            // Eğer yol doğrudan resources/views ile başlıyorsa, uygulama genelindeki view olarak kabul et
            $fullPath = BASE_PATH . '/' . $viewPath;
        } else {
            // Varsayılan olarak plugins dizinindeki bir view'ı hedefle
            // Örneğin: 'auth_login/views/view.php' -> BASE_PATH/plugins/auth_login/views/view.php
            // Bu formatta gelmesi beklenir.
            $fullPath = BASE_PATH . '/plugins/' . $viewPath;
        }

        error_log("Attempting to load view: " . $fullPath); // BURAYI EKLEYİN


        if (!file_exists($fullPath)) {
            $errorMessage = "View file not found: " . $fullPath;
            if (defined('APP_DEBUG') && APP_DEBUG) {
                throw new \RuntimeException($errorMessage);
            } else {
                error_log($errorMessage);
                return new self("An internal error occurred while rendering the page.", 500);
            }
        }
        require $fullPath;

        $content = ob_get_clean();

        $response = new self($content, $statusCode);
        $response->setHeaders($headers);
        return $response;
    }

    /**
     * Bir görünüm dosyasını doğrudan çıktıya render eder (Response nesnesi döndürmez).
     * Bu metod, özellikle partial'lar, widget'lar veya aksiyon callback'lerinden içerik basmak için kullanılır.
     * Output buffer'a doğrudan yazar.
     *
     * @param string $viewName Görünüm dosyasının adı (örn: 'plugin_adi::view_adi' veya 'resources/views/header' veya 'plugin_adi/views/partial_name')
     * @param array $data Görünüme gönderilecek veriler
     */
    public static function renderPartial(string $viewName, array $data = []): void
    {
        $fullPath = self::resolvePathForPartial($viewName); // Yeni yardımcı metodu çağırın

        if (!file_exists($fullPath)) {
            $errorMessage = "Partial View file not found: " . $fullPath;
            if (defined('APP_DEBUG') && APP_DEBUG) {
                // Hata ayıklama modunda hatayı doğrudan HTML yorumu olarak göster
                echo "";
            } else {
                error_log($errorMessage);
            }
            return; // Dosya bulunamazsa hiçbir şey yapma
        }

        extract($data); // Verileri görünümde kullanılabilir hale getir
        require $fullPath; // Görünüm dosyasını doğrudan dahil et, çıktısı o anki tampona gidecek
    }


    /**
     * Görünüm adını (örn. 'plugin_adi::view_adi' veya 'resources/views/view_name' veya 'plugin_adi/views/view_name') tam dosya yoluna çevirir.
     * Bu metod, 'renderPartial' için özel olarak görünüm yollarını çözer.
     *
     * @param string $viewName Çözümlenecek görünüm adı
     * @return string Tam dosya yolu
     */
    protected static function resolvePathForPartial(string $viewName): string
    {
        $baseResourceViewsPath = APP_ROOT_PATH . '/resources/views/';

        // 1. Durum: 'pluginAdi::viewAdi' formatı (en çok istenen)
        if (str_contains($viewName, '::')) {
            list($pluginName, $actualViewName) = explode('::', $viewName, 2);
            return APP_ROOT_PATH . '/plugins/' . $pluginName . '/views/' . $actualViewName . '.php';
        } 
        // 2. Durum: 'resources/views/tam_yol' formatı (eğer resources altında tam yol verildiyse)
        else if (str_starts_with($viewName, 'resources/views/')) {
            return APP_ROOT_PATH . '/' . $viewName . '.php'; 
        }
        // 3. Durum: 'pluginAdi/views/viewAdi.php' formatı (eski plugin view yolu)
        // İlk olarak plugin içinde bu formatı arayalım
        else if (str_contains($viewName, '/views/')) { // Basit bir kontrol
            $pluginViewPath = APP_ROOT_PATH . '/plugins/' . $viewName; // Uzantı muhtemelen dahil
            if (file_exists($pluginViewPath)) {
                 return $pluginViewPath;
            }
            // Eğer uzantısız verilmişse (.php ekleyelim)
            $pluginViewPath .= '.php';
            if (file_exists($pluginViewPath)) {
                 return $pluginViewPath;
            }
        }
        
        // 4. Durum: Sadece 'view_adı' veya 'alt_klasör/view_adı' formatı (resources/views altında ara)
        // Eğer yukarıdaki formatlara uymuyorsa, resources/views altında arıyoruz.
        return $baseResourceViewsPath . $viewName . '.php';
    }
}