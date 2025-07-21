<?php

namespace App\Core\Exceptions;

use Exception; // PHP'nin temel Exception sınıfından türetilecek
use Throwable;

/**
 * Eklenti (plugin) ile ilgili hataları temsil eden özel istisna sınıfı.
 */
class PluginException extends Exception
{
    /**
     * PluginException sınıfının yapıcı metodu.
     *
     * @param string $message Hata mesajı
     * @param int $code Hata kodu (varsayılan 0)
     * @param Throwable|null $previous Önceki istisna (isteğe bağlı)
     */
    public function __construct(string $message = "Plugin related error", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}