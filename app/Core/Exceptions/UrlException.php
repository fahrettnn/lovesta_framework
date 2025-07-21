<?php

namespace App\Core\Exceptions;

use Exception; // PHP'nin temel Exception sınıfından türetilecek
use Throwable;

/**
 * URL ile ilgili hataları (örn. 404, 405) temsil eden özel istisna sınıfı.
 */
class UrlException extends Exception
{
    /**
     * UrlException sınıfının yapıcı metodu.
     *
     * @param string $message Hata mesajı
     * @param int $code HTTP durum kodu (404, 405 vb.)
     * @param Throwable|null $previous Önceki istisna (isteğe bağlı)
     */
    public function __construct(string $message = "URL Not Found", int $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}