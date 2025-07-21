<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use App\Core\Application;
use Symfony\Component\Console\Application as SymfonyApplication;

/**
 * Yeni bir plugin oluşturan CLI komutu.
 */
class MakePluginCommand extends Command
{
    protected Application $app;
    protected string $stubsPath;

    protected static $defaultName = 'make:plugin';
    protected static $defaultDescription = 'Creates a new plugin with a basic structure.';

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->stubsPath = BASE_PATH . '/app/Console/Commands/Stubs/Plugin/';
        parent::__construct('make:plugin');
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command creates a new plugin folder with essential files like config.json, plugin.php, controllers, models, views, and migrations.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The slug of the plugin to create (e.g., my_new_plugin).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pluginSlug = $input->getArgument('name');
        $output->writeln(sprintf('<lovesta_info>Attempting to create plugin: %s</lovesta_info>', $pluginSlug));

        $pluginsDir = BASE_PATH . '/plugins';
        $newPluginPath = $pluginsDir . '/' . $pluginSlug;

        if (is_dir($newPluginPath)) {
            $output->writeln(sprintf('<lovesta_warning>Plugin "%s" already exists at "%s". Aborting.</lovesta_warning>', $pluginSlug, $newPluginPath));
            return Command::FAILURE;
        }

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $output->writeln('<lovesta_line>---------------------------------------</lovesta_line>');
        $output->writeln('<lovesta_prompt>Plugin Components Setup:</lovesta_prompt>');
        $output->writeln('<lovesta_line>---------------------------------------</lovesta_line>');

        $createOptions = [
            'config_file' => ['file', 'config.json', true],
            'plugin_file' => ['file', 'plugin.php', true],
            'controllers' => ['directory', 'controllers/', true],
            'models' => ['directory', 'models/', true],
            'views' => ['directory', 'views/', true],
            'migrations' => ['directory', 'migrations/', true],
            'assets' => ['directory', 'assets/', true],
            'services' => ['directory', 'services/', true],
        ];

        $userChoices = [];
        foreach ($createOptions as $key => &$details) {
            $type = $details[0];
            $name = $details[1];
            $default = $details[2];

            if ($key === 'config_file' || $key === 'plugin_file') {
                $userChoices[$key] = true;
                continue;
            }

            $questionText = sprintf('<question>Create %s %s? (Y/n)</question> ', $type, $name);
            $question = new ConfirmationQuestion($questionText, $default);
            $userChoices[$key] = $helper->ask($input, $output, $question);
        }

        $generalQuestion = new ConfirmationQuestion('<question>Proceed with plugin creation based on your choices? (Y/n)</question> ', true);
        if (!$helper->ask($input, $output, $generalQuestion)) {
            $output->writeln('<lovesta_info>Plugin creation aborted by user.</lovesta_info>');
            return Command::SUCCESS;
        }

        try {
            $output->writeln('<lovesta_info>Creating plugin structure...</lovesta_info>');
            $this->createPluginStructure($newPluginPath, $pluginSlug, $output, $userChoices);
            $output->writeln(sprintf('<lovesta_success>Plugin "%s" created successfully!</lovesta_success>', $pluginSlug));
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<lovesta_error>Error creating plugin "%s": %s</lovesta_error>', $pluginSlug, $e->getMessage()));
            error_log('MakePluginCommand Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function createPluginStructure(string $pluginPath, string $pluginSlug, OutputInterface $output, array $userChoices): void
    {
        if (!mkdir($pluginPath, 0777, true)) { // İzinleri 0777'ye yükselttim (test ortamı için)
            throw new \RuntimeException('Failed to create plugin directory: ' . $pluginPath);
        }
        $output->writeln(sprintf('<lovesta_info>  Created directory: %s</lovesta_info>', $pluginPath));

        $pluginName = $this->getPluginNameFromSlug($pluginSlug);
        $pluginNamespace = $this->getPluginNamespace($pluginSlug);
        $pluginConstName = $this->getPluginConst($pluginSlug);
        $controllerClassName = $this->getPluginClass($pluginSlug, 'Controller');
        $migrationClassName = $this->getPluginClass($pluginSlug);
        $modelClassName = $this->getPluginClass($pluginSlug, 'Model');
        $serviceClassName = $this->getPluginClass($pluginSlug, 'Service');
        
        // TÜM STUB'LAR İÇİN ORTAK YER TUTUCULAR DİZİSİ
        // Bu dizideki anahtarlar, stub dosyalarındaki yer tutucu isimleriyle birebir eşleşmeli.
        $basePlaceholders = [
            '{$PLUGIN_SLUG}' => $pluginSlug,
            '{$PLUGIN_NAME}' => $pluginName,
            '{$PLUGIN_NAMESPACE}' => $pluginNamespace,
            '{$PLUGIN_CONST_NAME}' => $pluginConstName,
            '{$PLUGIN_CONTROLLER_CLASS_NAME}' => $controllerClassName, // Plugin.php stub'ı için
            '{$PLUGIN_MODEL_CLASS_NAME}' => $modelClassName, // Plugin.php stub'ı için
            '{$PLUGIN_SERVICE_CLASS_NAME}' => $serviceClassName, // Plugin.php stub'ı için
            '{$PLUGIN_MIGRATION_CLASS_NAME}' => $migrationClassName,
            '{$TABLE_NAME}' => str_replace('-', '_', $pluginSlug) . '_table', // Migration stub'ı için
            
            // Bu iki placeholder, her bileşen için dinamik olarak güncellenmeli.
            // Başlangıçta boş bırakılıyor, çünkü str_replace'e her döngüde doğru değerler eklenecek.
            '{$PLUGIN_CLASS_NAME}' => '', 
        ];


        // config.json
        if ($userChoices['config_file']) {
            $configStub = file_get_contents($this->stubsPath . 'plugin.json.stub');
            $finalConfigContent = str_replace(array_keys($basePlaceholders), array_values($basePlaceholders), $configStub);
            file_put_contents($pluginPath . '/config.json', $finalConfigContent);
            $output->writeln('<lovesta_info>  Created config.json</lovesta_info>');
        }

        // plugin.php
        if ($userChoices['plugin_file']) {
            $pluginPhpStub = file_get_contents($this->stubsPath . 'plugin.php.stub');
            $finalPluginPhpContent = str_replace(array_keys($basePlaceholders), array_values($basePlaceholders), $pluginPhpStub);
            file_put_contents($pluginPath . '/plugin.php', $finalPluginPhpContent);
            $output->writeln('<lovesta_info>  Created plugin.php</lovesta_info>');
        }

        $subdirsToCreate = [
            'controllers' => ['controllers/', 'controller.php.stub'],
            'models' => ['models/', 'model.php.stub'],
            'views' => ['views/', 'view.php.stub'],
            'migrations' => ['migrations/', 'migration.php.stub'],
            'assets' => ['assets/', null],
            'services' => ['services/', 'service.php.stub'],
        ];

        foreach ($subdirsToCreate as $choiceKey => $data) {
            $subdir = $data[0];
            $stubFile = $data[1];
            $dirPath = $pluginPath . '/' . rtrim($subdir, '/');

            if ($choiceKey === 'assets' && $userChoices[$choiceKey]) {
                if (!mkdir($dirPath, 0777, true)) { throw new \RuntimeException('Failed to create assets directory: ' . $dirPath); }
                $output->writeln(sprintf('<lovesta_info>  Created directory: %s</lovesta_info>', $dirPath));
                if (!mkdir($dirPath . '/css', 0777, true)) { throw new \RuntimeException('Failed to create assets/css directory: ' . $dirPath . '/css'); }
                $output->writeln(sprintf('<lovesta_info>  Created directory: %s</lovesta_info>', $dirPath . '/css'));
                if (!mkdir($dirPath . '/js', 0777, true)) { throw new \RuntimeException('Failed to create assets/js directory: ' . $dirPath . '/js'); }
                $output->writeln(sprintf('<lovesta_info>  Created directory: %s</lovesta_info>', $dirPath . '/js'));
                // YENİ: Img ve fonts klasörlerini de oluştur
                if (!mkdir($dirPath . '/img', 0777, true)) { throw new \RuntimeException('Failed to create assets/img directory: ' . $dirPath . '/img'); }
                $output->writeln(sprintf('<lovesta_info>  Created directory: %s</lovesta_info>', $dirPath . '/img'));
                if (!mkdir($dirPath . '/fonts', 0777, true)) { throw new \RuntimeException('Failed to create assets/fonts directory: ' . $dirPath . '/fonts'); }
                $output->writeln(sprintf('<lovesta_info>  Created directory: %s</lovesta_info>', $dirPath . '/fonts'));
                continue;
            }

            if ($userChoices[$choiceKey]) {
                if (!mkdir($dirPath, 0777, true)) {
                    throw new \RuntimeException('Failed to create plugin subdirectory: ' . $dirPath);
                }
                $output->writeln(sprintf('<lovesta_info>  Created directory: %s</lovesta_info>', $dirPath));

                if ($stubFile) {
                    $stubContent = file_get_contents($this->stubsPath . $stubFile);
                    
                    $fileName = '';
                    $finalContent = '';
                    
                    // Bileşen türüne göre PLUGIN_NAMESPACE ve PLUGIN_CLASS_NAME değerlerini ayarla
                    $currentComponentName = '';
                    $currentComponentNamespace = '';

                    if ($choiceKey === 'controllers') {
                        $currentComponentName = $controllerClassName;
                        $currentComponentNamespace = $this->getPluginNamespace($pluginSlug, 'Controllers');
                        $fileName = $currentComponentName . '.php';
                    } elseif ($choiceKey === 'models') {
                        $currentComponentName = $modelClassName;
                        $currentComponentNamespace = $this->getPluginNamespace($pluginSlug, 'Models');
                        $fileName = $currentComponentName . '.php';
                    } elseif ($choiceKey === 'services') {
                        $currentComponentName = $serviceClassName;
                        $currentComponentNamespace = $this->getPluginNamespace($pluginSlug, 'Services');
                        $fileName = $currentComponentName . '.php';
                    } elseif ($choiceKey === 'views') {
                        $fileName = 'view.php';
                        // View için PLUGIN_NAMESPACE ve PLUGIN_NAME kullanılıyor, PLUGIN_NAMESPACE/CLASS_NAME değil
                        $currentComponentNamespace = $pluginNamespace; // View namespace'i plugin kök namespace'i ile aynıdır
                        $currentComponentName = 'view'; // View dosyasının adı
                    } elseif ($choiceKey === 'migrations') {
                        $migrationBaseName = 'create_' . str_replace('-', '_', $pluginSlug) . '_table';
                        $fileName = date('Y-m-d_His') . '_' . $migrationBaseName . '.php';
                        $migrationClassName = $this->getPluginClass($pluginSlug, 'Migration', $migrationBaseName);
                        
                        $currentComponentName = $migrationClassName;
                        $currentComponentNamespace = $this->getPluginNamespace($pluginSlug, 'Migrations');
                    }
                    
                    // TÜM YER TUTUCULARI TEK BİR DİZİDE TOPLA VE DEĞİŞTİR
                    // Bu, basePlaceholders'ı kopyalar ve üzerine component'e özel değerleri yazar.
                    $combinedPlaceholders = array_merge($basePlaceholders, [
                        '{$PLUGIN_NAMESPACE}' => $currentComponentNamespace,
                        '{$PLUGIN_CLASS_NAME}' => $currentComponentName,
                    ]);
                    
                    $finalContent = str_replace(array_keys($combinedPlaceholders), array_values($combinedPlaceholders), $stubContent);
                    
                    if ($fileName) {
                        file_put_contents($dirPath . '/' . $fileName, $finalContent);
                        $output->writeln(sprintf('<lovesta_info>    Created example %s.</lovesta_info>', $stubFile));
                    }
                }
            }
        }
    }

    protected function getPluginNamespace(string $pluginSlug, string $subfolder = ''): string
    {
        $cleanedSlug = str_replace('_', ' ', $pluginSlug);
        $capitalizedSlug = ucwords($cleanedSlug);
        $finalPluginNamespacePart = str_replace(' ', '', $capitalizedSlug);

        if ($subfolder) {
            return $finalPluginNamespacePart . '\\' . $subfolder;
        }
        return $finalPluginNamespacePart;
    }

    protected function getPluginConst(string $pluginSlug): string
    {
        return strtoupper(str_replace('-', '_', $pluginSlug)) . '_PLUGIN_PATH';
    }

    protected function getPluginClass(string $pluginSlug, string $type = '', string $migrationBaseName = ''): string
    {
        $classNameBase = str_replace('_', '', ucwords(str_replace('-', ' ', $pluginSlug)));
        
        if ($type === 'Controller') {
            return $classNameBase . 'Controller';
        } elseif ($type === 'Model') {
            return $classNameBase . 'Model';
        } elseif ($type === 'Migration') {
            if ($migrationBaseName) {
                $baseNameWithoutPrefix = preg_replace('/^create_/', '', $migrationBaseName);
                $finalClassName = str_replace('_', '', ucwords($baseNameWithoutPrefix, '_'));
                return ucfirst($finalClassName);
            }
            return $classNameBase;
        } elseif ($type === 'Service') {
            return $classNameBase . 'Service';
        }
        return $classNameBase;
    }

    protected function getPluginNameFromSlug(string $pluginSlug): string
    {
        return ucwords(str_replace('_', ' ', $pluginSlug));
    }
}