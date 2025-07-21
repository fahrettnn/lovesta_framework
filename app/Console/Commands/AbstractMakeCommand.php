<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption; // InputOption'ı import et!
use App\Core\Application;
use App\Core\Helpers\PluginHelper;

/**
 * Yeni bileşenler (controller, model vb.) oluşturan CLI komutları için soyut temel sınıf.
 * Plugin içinde veya uygulama genelinde bileşen oluşturma mantığını sağlar.
 */
abstract class AbstractMakeCommand extends Command
{
    protected Application $app;
    protected PluginHelper $pluginHelper;
    protected string $stubsPath;
    protected string $componentType;

    public function __construct(Application $app, PluginHelper $pluginHelper)
    {
        $this->app = $app;
        $this->pluginHelper = $pluginHelper;
        $this->stubsPath = BASE_PATH . '/app/Console/Commands/Stubs/';
        parent::__construct(static::$defaultName); 
    }

    protected function configure(): void
    {
        // DÜZELTİLDİ: 'name' argümanı ilk ve zorunlu argüman oldu.
        $this
            ->addArgument(
                'name', // Oluşturulacak bileşenin adı (örn: MyController, ProductModel, MyMigration)
                InputArgument::REQUIRED,
                'The name of the component to create (e.g., MyController, ProductModel, MyMigration).'
            )
            // DÜZELTİLDİ: 'plugin_slug' artık bir Opsiyon (--plugin veya -p)
            ->addOption(
                'plugin', // Opsiyonun adı (kullanımı: --plugin=my_plugin veya -p my_plugin)
                'p', // Opsiyonun kısa adı (isteğe bağlı)
                InputOption::VALUE_OPTIONAL, // Değer alır ve isteğe bağlıdır
                'The slug of the plugin where the component will be created (e.g., auth_login). Defaults to "database" for main app.',
                'database' // Varsayılan değeri
            );
    }

    protected function prepareComponentContent(string $stubContent, string $pluginSlug, string $componentName): string
    {
        $pluginNamespace = $this->getPluginNamespace($pluginSlug);
        $componentClassName = $this->getComponentClassName($componentName);

        $placeholders = [
            '{$PLUGIN_SLUG}' => $pluginSlug,
            '{$PLUGIN_NAME}' => $this->getPluginNameFromSlug($pluginSlug),
            '{$PLUGIN_NAMESPACE}' => $pluginNamespace,
            '{$PLUGIN_CLASS_NAME}' => $componentClassName,
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $stubContent);
    }

    protected function getComponentClassName(string $name): string
    {
        $className = str_replace(['_', '-'], ' ', $name);
        $className = ucwords($className);
        $className = str_replace(' ', '', $className);
        return $className;
    }

    protected function getPluginNamespace(string $pluginSlug): string
    {
        $cleanedSlug = str_replace('_', ' ', $pluginSlug);
        $capitalizedSlug = ucwords($cleanedSlug);
        return str_replace(' ', '', $capitalizedSlug);
    }

    protected function getComponentNamespace(string $pluginSlug, string $componentType): string
    {
        $baseNamespace = $this->getPluginNamespace($pluginSlug);
        return $baseNamespace . '\\' . ucfirst($componentType);
    }

    protected function getPluginNameFromSlug(string $pluginSlug): string
    {
        return ucwords(str_replace('_', ' ', $pluginSlug));
    }

    protected function writeOutput(OutputInterface $output, string $message, string $type = 'info'): void
    {
        $styledMessage = sprintf('<lovesta_%s>%s</lovesta_%s>', $type, $message, $type);
        $output->writeln($styledMessage);
        $this->app->getContainer()->get(\Monolog\Logger::class)->info(strip_tags($styledMessage));
    }

    abstract protected function getComponentFilePath(string $pluginPath, string $componentName): string;

    // execute metodunu burada bırakmıyorum, çünkü MakeControllerCommand gibi alt sınıflarda implement edilecek.
    // Ancak alt sınıflar execute metotlarını güncelleyerek plugin_slug'ı opsiyondan almalı.
}