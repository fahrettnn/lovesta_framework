<?php

namespace App\Core\Helpers;

use App\Core\Application;
use App\Core\Exceptions\PluginException;
use App\Core\Http\Router;
use Psr\Container\ContainerInterface;
use Monolog\Logger; // Monolog Logger sınıfını import et
use SplFileInfo;

class PluginHelper
{
    protected Application $app;
    protected array $loadedPlugins = [];
    protected string $pluginsDir;
    protected Logger $logger; // YENİ: Logger özelliği eklendi

    /**
     * PluginHelper sınıfının yapıcı metodu.
     * @param Application $app
     * @param Logger $logger // YENİ: Logger bağımlılığı eklendi
     */
    public function __construct(Application $app, Logger $logger)
    {
        $this->app = $app;
        $this->logger = $logger; // YENİ: Logger atanıyor
        $this->pluginsDir = BASE_PATH . '/plugins';
    }

    public function loadAllPlugins(): void
    {
        if (!is_dir($this->pluginsDir)) {
            // Log mesajını error_log yerine Monolog ile yazdır
            $this->logger->warning("Plugins directory not found: " . $this->pluginsDir);
            return;
        }

        $pluginDirs = glob($this->pluginsDir . '/*', GLOB_ONLYDIR);

        foreach ($pluginDirs as $pluginPath) {
            $pluginSlug = basename($pluginPath);
            try {
                $this->loadPlugin($pluginSlug, $pluginPath);
                $this->loadedPlugins[$pluginSlug] = true;
            } catch (PluginException $e) {
                // Her zaman hata seviyesinde logla
                $this->logger->error("Failed to load plugin '{$pluginSlug}': " . $e->getMessage());
            } catch (\Throwable $e) {
                // Beklenmedik hataları da hata seviyesinde logla
                $this->logger->error("An unexpected error occurred while loading plugin '{$pluginSlug}': " . $e->getMessage());
            }
        }
    }

    protected function loadPlugin(string $pluginSlug, string $pluginPath): void
    {
        $pluginConfigFile = $pluginPath . '/config.json';
        $pluginMainFile = $pluginPath . '/plugin.php';

        if (!file_exists($pluginConfigFile)) {
            throw new PluginException("Plugin config file not found: {$pluginConfigFile}");
        }
        $config = json_decode(file_get_contents($pluginConfigFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PluginException("Invalid JSON in plugin config file: {$pluginConfigFile} - " . json_last_error_msg());
        }

        $pluginName = $config['name'] ?? $pluginSlug;
        $pluginDescription = $config['description'] ?? 'No description provided.';
        $pluginStatus = $config['status'] ?? true;

        if ($pluginStatus !== true) {
            // Pasif eklentileri bilgi seviyesinde logla (Monolog kullan)
            $this->logger->info("Plugin '{$pluginSlug}' is inactive. Skipping.");
            return;
        }

        $this->app->getConfig()->set("plugins.{$pluginSlug}", $config);

        if (!file_exists($pluginMainFile)) {
            throw new PluginException("Plugin main file not found: {$pluginMainFile}");
        }

        $router = $this->app->getContainer()->get(Router::class);
        $container = $this->app->getContainer();
        $actionFilter = $this->app->getContainer()->get(ActionFilterHelper::class);

        define(strtoupper(str_replace('-', '_', $pluginSlug)) . '_PLUGIN_PATH', $pluginPath);

        $pluginBootstrap = require $pluginMainFile;
        if (is_callable($pluginBootstrap)) {
            $pluginBootstrap($router, $container, $actionFilter);
        }

        $actionFilter->doAction('plugin_loaded', $pluginSlug, $pluginPath);
        // BAŞARILI YÜKLEME MESAJINI DA MONOLOG İLE LOGLA (INFO seviyesinde)
        // Bu mesajlar artık varsayılan olarak CLI çıktısında görünmeyecek, sadece app.log'da olacak.
        $this->logger->info("Plugin '{$pluginSlug}' loaded successfully.");
    }

    public function isPluginLoaded(string $pluginSlug): bool
    {
        return isset($this->loadedPlugins[$pluginSlug]);
    }

    public function getLoadedPlugins(): array
    {
        return array_keys($this->loadedPlugins);
    }
}