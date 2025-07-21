<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use App\Core\Application;
use App\Core\Helpers\PluginHelper;
/**
 * Yeni bir servis oluşturan CLI komutu.
 */
class MakeServiceCommand extends AbstractMakeCommand
{
    protected static $defaultName = 'make:service';
    protected static $defaultDescription = 'Creates a new service within a specified plugin.';
    protected string $componentType = 'Services';

    public function __construct(Application $app, PluginHelper $pluginHelper)
    {
        parent::__construct($app, $pluginHelper);
    }

    protected function configure(): void
    {
        parent::configure(); // AbstractMakeCommand'ın configure metodunu çağırır.
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // DÜZELTİLDİ: plugin_slug'ı artık opsiyon olarak alıyoruz
        $pluginSlug = $input->getOption('plugin');
        $serviceName = $input->getArgument('name');
        $componentType = $this->componentType;

        $this->writeOutput($output, sprintf('Attempting to create %s: %s in %s', $componentType, $serviceName, $pluginSlug === 'database' ? 'main database directory' : 'plugin: ' . $pluginSlug), 'info');

        $pluginPath = BASE_PATH . '/plugins/' . $pluginSlug;
        if ($pluginSlug === 'database') {
            $componentDirPath = BASE_PATH . '/app/' . strtolower($componentType);
        } else {
            if (!is_dir($pluginPath)) {
                $this->writeOutput($output, sprintf('Error: Plugin "%s" not found at "%s".', $pluginSlug, $pluginPath), 'error');
                return Command::FAILURE;
            }
            $componentDirPath = $pluginPath . '/' . strtolower($componentType);
        }

        if (!is_dir($componentDirPath)) {
            if (!mkdir($componentDirPath, 0755, true)) {
                $this->writeOutput($output, sprintf('Error: Failed to create directory: %s', $componentDirPath), 'error');
                return Command::FAILURE;
            }
            $this->writeOutput($output, sprintf('Created directory: %s', $componentDirPath), 'info');
        }

        $componentFileName = $this->getComponentClassName($serviceName) . '.php';
        $componentFilePath = $componentDirPath . '/' . $componentFileName;

        if (file_exists($componentFilePath)) {
            $this->writeOutput($output, sprintf('Warning: %s "%s" already exists at "%s". Aborting.', $componentType, $serviceName, $componentFilePath), 'warning');
            return Command::FAILURE;
        }

        $stubPath = BASE_PATH . '/app/Console/Commands/Stubs/Plugin/service.php.stub';
        if (!file_exists($stubPath)) {
            $this->writeOutput($output, sprintf('Error: Service stub file not found: %s', $stubPath), 'error');
            return Command::FAILURE;
        }
        $stubContent = file_get_contents($stubPath);

        $finalContent = $this->prepareComponentContent($stubContent, $pluginSlug, $serviceName);

        if (file_put_contents($componentFilePath, $finalContent) === false) {
            $this->writeOutput($output, sprintf('Error: Failed to write %s file to: %s', $componentType, $componentFilePath), 'error');
            return Command::FAILURE;
        }

        $this->writeOutput($output, sprintf('%s "%s" created successfully at "%s".', $componentType, $serviceName, $componentFilePath), 'success');
        return Command::SUCCESS;
    }

    protected function getComponentFilePath(string $pluginPath, string $componentName): string
    {
        return $pluginPath . '/' . strtolower($this->componentType) . '/' . $this->getComponentClassName($componentName) . '.php';
    }
}