<?php

namespace App\Core\Exceptions;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Config; // Konfigürasyonu kullanmak için
use Throwable;
use Monolog\Logger; // Monolog'u kullanmak için
use Monolog\Handler\StreamHandler;

class Handler
{
    protected Config $config;
    protected Logger $logger;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->setupLogger();
    }

    protected function setupLogger(): void
    {
        $logPath = BASE_PATH . '/storage/logs/app.log';
        $this->logger = new Logger('application');
        $this->logger->pushHandler(new StreamHandler($logPath, Logger::ERROR));
    }

    /**
     * Yakalanan bir hatayı işler.
     *
     * @param Throwable $e Hata nesnesi
     * @param Request $request Mevcut istek nesnesi
     * @return Response Hata yanıtı
     */
    public function handle(Throwable $e, Request $request): Response
    {
        $this->logger->error($e->getMessage(), ['exception' => $e]);

        if ($this->config->get('app.debug', false)) {
            // Debug modunda Tracy hatayı zaten gösterecek.
            // Burada sadece Response nesnesini döndürüyoruz ki uygulama akışı devam etsin.
            // Tracy'nin kendi render mekanizması olduğu için burada HTML üretmeyeceğiz.
            // Eğer Tracy kullanmak istemezseniz burada daha detaylı bir hata sayfası render edebilirsiniz.
            return new Response("An error occurred: " . $e->getMessage() . "<br>See Tracy for details.", 500);
        } else {
            // Üretim modunda genel bir hata sayfası göster
            // resources/views/500.php dosyasını render et (ileride oluşturulacak)
            try {
                return Response::view('500.php', [], 500); // Kökten resources/views/500.php varsayımı
            } catch (Throwable $viewException) {
                error_log("500 view could not be rendered: " . $viewException->getMessage());
                return new Response("<h1>500 Internal Server Error</h1><p>An unexpected error occurred.</p>", 500);
            }
        }
    }
}