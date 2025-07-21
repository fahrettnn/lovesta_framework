<?php

namespace App\Core\Helpers;

use App\Core\Application; // Application nesnesine erişim için

/**
 * Eklentiler arası iletişim ve esneklik sağlayan Action/Filter (Hook) sistemi.
 */
class ActionFilterHelper
{
    protected array $actions = [];

    /**
     * @var array Kayıtlı filtreler (fonksiyonlar)
     * Yapı: ['hook_name' => [['callback', priority], ...]]
     */
    protected array $filters = [];

    /**
     * @var Application Uygulama örneği
     */
    protected Application $app; // Property'nin tanımlı olduğundan emin olalım

    /**
     * ActionFilterHelper'ın yapıcı metodu.
     * Gerekirse Application instance'ı alabiliriz, bu sayede callback'ler içerisinde DI Container'a erişilebilir.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app; // Düzeltildi: Application instance'ını özelliğe ata
    }

    /**
     * Belirli bir aksiyon için bir callback (fonksiyon veya metod) kaydeder.
     *
     * @param string $hookName Aksiyonun adı
     * @param callable $callback Çağrılacak fonksiyon/metod
     * @param int $priority Callback'in çalışma önceliği (düşük sayı daha yüksek öncelik)
     */
    public function addAction(string $hookName, callable $callback, int $priority = 10): void
    {
        $this->actions[$hookName][] = ['callback' => $callback, 'priority' => $priority];
        usort($this->actions[$hookName], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Kayıtlı tüm callback'leri belirli bir aksiyon için çalıştırır.
     *
     * @param string $hookName Aksiyonun adı
     * @param mixed ...$args Callback'lere iletilecek argümanlar
     */
    public function doAction(string $hookName, ...$args): void
    {
        if (isset($this->actions[$hookName])) {
            foreach ($this->actions[$hookName] as $item) {
                // Callback'i IoC Container üzerinden çağırabiliriz
                // Böylece callback'ler de bağımlılık enjeksiyonundan faydalanabilir.
                $this->callCallback($item['callback'], $args);
            }
        }
    }

    /**
     * Belirli bir filtre için bir callback (fonksiyon veya metod) kaydeder.
     *
     * @param string $hookName Filtrenin adı
     * @param callable $callback Çağrılacak fonksiyon/metod
     * @param int $priority Callback'in çalışma önceliği (düşük sayı daha yüksek öncelik)
     */
    public function addFilter(string $hookName, callable $callback, int $priority = 10): void
    {
        $this->filters[$hookName][] = ['callback' => $callback, 'priority' => $priority];
        usort($this->filters[$hookName], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Bir değeri kayıtlı filtrelerden geçirir ve filtrelenmiş değeri döndürür.
     *
     * @param string $hookName Filtrenin adı
     * @param mixed $value Filtrelenecek başlangıç değeri
     * @param mixed ...$args Filtre callback'lerine iletilecek ek argümanlar
     * @return mixed Filtrelenmiş değer
     */
    public function applyFilter(string $hookName, $value, ...$args): mixed
    {
        if (isset($this->filters[$hookName])) {
            foreach ($this->filters[$hookName] as $item) {
                // Callback'i IoC Container üzerinden çağırabiliriz
                // İlk argüman her zaman filtrelenen değerdir
                $value = $this->callCallback($item['callback'], array_merge([$value], $args));
            }
        }
        return $value;
    }

    /**
     * Bir callback'i IoC Container aracılığıyla çağırır.
     * Bu sayede callback'ler de bağımlılık enjeksiyonundan faydalanabilir.
     *
     * @param callable $callback Çağrılacak fonksiyon/metod
     * @param array $args Callback'e iletilecek argümanlar
     * @return mixed
     */
    protected function callCallback(callable $callback, array $args): mixed
    {
        // Container'ı alırken `$this->app` özelliğini kullan
        if (isset($this->app) && $this->app->getContainer()) {
            return $this->app->getContainer()->call($callback, $args);
        } else {
            // Eğer Application veya Container mevcut değilse (ki bu olmamalı)
            return call_user_func_array($callback, $args);
        }
    }
}