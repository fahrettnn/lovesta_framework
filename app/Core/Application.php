<?php

namespace App\Core;

use App\Core\Config;
use DI\ContainerBuilder;
use DI\Container;
use App\Core\Helpers\PluginHelper;
use App\Core\Helpers\ActionFilterHelper;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Router;
use App\Core\Http\Kernel;
use App\Core\Exceptions\Handler as ExceptionHandler;
use App\Core\Database\Connection;
use App\Core\Database\Model;
use App\Core\Database\Migration;
use PDO;
use Psr\Container\ContainerInterface;
use Monolog\Logger; // Monolog Logger sınıfını import et
use Monolog\Handler\StreamHandler; // Monolog StreamHandler sınıfını import et
use function DI\autowire;
use function DI\get;
use function DI\create;


class Application
{
    protected Config $config;
    protected Container $container;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->setupContainer();
    }

    protected function setupContainer(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true);

        $containerBuilder->addDefinitions([
            Application::class => $this,
            Config::class => $this->config,
            'config' => $this->config,

            // YENİ: Monolog Logger servisini tanımla (uygulama logları için)
            Logger::class => function (ContainerInterface $c) {
                $logger = new Logger('AppLogger');
                // app.log dosyasına info seviyesinden itibaren logları yaz
                $logger->pushHandler(new StreamHandler(BASE_PATH . '/storage/logs/app.log', Logger::WARNING));
                return $logger;
            },

            Request::class => autowire(Request::class),
            Response::class => autowire(Response::class),
            Router::class => autowire(Router::class),
            Kernel::class => autowire(Kernel::class),
            ExceptionHandler::class => autowire(ExceptionHandler::class),

            // PluginHelper artık Logger bağımlılığını alacak
            PluginHelper::class => autowire(PluginHelper::class), 
            ActionFilterHelper::class => autowire(ActionFilterHelper::class),

            Connection::class => autowire(Connection::class),
            PDO::class => function (ContainerInterface $c) {
                /** @var Connection $connection */
                $connection = $c->get(Connection::class);
                return $connection->getPdo();
            },
            Migration::class => autowire(Migration::class),
            Model::class => autowire(Model::class),
        ]);

        $this->container = $containerBuilder->build();
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function handleRequest(): void
    {
        // Bu metod artık kullanılmayacak.
    }

    public function bootPlugins(): void
    {
        /** @var PluginHelper $pluginHelper */
        $pluginHelper = $this->container->get(PluginHelper::class);
        $pluginHelper->loadAllPlugins();
    }
}