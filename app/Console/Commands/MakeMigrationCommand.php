<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

use App\Core\Application;
use App\Core\Helpers\PluginHelper;
use Symfony\Component\Console\Input\InputOption;

/**
 * Yeni bir migration dosyası oluşturan CLI komutu.
 */
class MakeMigrationCommand extends AbstractMakeCommand
{
    protected static $defaultName = 'make:migration';
    protected static $defaultDescription = 'Creates a new migration file within a specified plugin or the main database migrations directory.';
    protected string $componentType = 'Migrations';

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
        $migrationBaseName = $input->getArgument('name');
        $componentType = $this->componentType;

        $this->writeOutput($output, sprintf('Attempting to create %s: %s in %s', $componentType, $migrationBaseName, $pluginSlug === 'database' ? 'main database directory' : 'plugin: ' . $pluginSlug), 'info');

        $targetPath = '';
        if ($pluginSlug === 'database') {
            $targetPath = BASE_PATH . '/database/migrations';
        } else {
            $targetPath = BASE_PATH . '/plugins/' . $pluginSlug . '/migrations';
            if (!is_dir(BASE_PATH . '/plugins/' . $pluginSlug)) {
                $this->writeOutput($output, sprintf('Error: Plugin "%s" not found at "%s".', $pluginSlug, BASE_PATH . '/plugins/' . $pluginSlug), 'error');
                return Command::FAILURE;
            }
        }

        if (!is_dir($targetPath)) {
            if (!mkdir($targetPath, 0755, true)) {
                $this->writeOutput($output, sprintf('Error: Failed to create directory: %s', $targetPath), 'error');
                return Command::FAILURE;
            }
            $this->writeOutput($output, sprintf('Created directory: %s', $targetPath), 'info');
        }

        $timestamp = date('Y-m-d_His');
        $cleanedMigrationName = str_replace(['-', ' '], '_', strtolower($migrationBaseName));
        $migrationFileName = $timestamp . '_' . $cleanedMigrationName . '.php';
        $migrationFilePath = $targetPath . '/' . $migrationFileName;

        if (file_exists($migrationFilePath)) {
            $this->writeOutput($output, sprintf('Warning: Migration file "%s" already exists at "%s". Aborting.', $migrationFileName, $migrationFilePath), 'warning');
            return Command::FAILURE;
        }

        $migrationClassName = $this->getComponentClassName($migrationBaseName);

        $stubPath = BASE_PATH . '/app/Console/Commands/Stubs/Plugin/migration.php.stub';
        if (!file_exists($stubPath)) {
            $this->writeOutput($output, sprintf('Error: Migration stub file not found: %s', $stubPath), 'error');
            return Command::FAILURE;
        }
        $stubContent = file_get_contents($stubPath);

        $placeholders = [
            '{$PLUGIN_NAMESPACE}\Migrations' => $this->getComponentNamespace($pluginSlug, 'Migrations'),
            '{$PLUGIN_MIGRATION_CLASS_NAME}' => $migrationClassName,
            '{$TABLE_NAME}' => $cleanedMigrationName,
        ];
        $finalContent = str_replace(array_keys($placeholders), array_values($placeholders), $stubContent);

        if (file_put_contents($migrationFilePath, $finalContent) === false) {
            $this->writeOutput($output, sprintf('Error: Failed to write %s file to: %s', $componentType, $migrationFilePath), 'error');
            return Command::FAILURE;
        }

        $this->writeOutput($output, sprintf('%s "%s" created successfully at "%s".', $componentType, $migrationFileName, $migrationFilePath), 'success');
        return Command::SUCCESS;
    }

    protected function getComponentFilePath(string $pluginPath, string $componentName): string
    {
        return $pluginPath . '/' . strtolower($this->componentType) . '/' . $this->getComponentClassName($componentName) . '.php';
    }
}