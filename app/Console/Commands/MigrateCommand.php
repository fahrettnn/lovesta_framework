<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use App\Core\Database\Migration;
use App\Core\Config;

/**
 * Veritabanı migration'larını çalıştıran CLI komutu.
 */
class MigrateCommand extends Command
{
    protected Migration $migration;
    protected Config $config;

    protected static $defaultName = 'migrate';
    protected static $defaultDescription = 'Runs database migrations or rolls back.';

    public function __construct(Migration $migration, Config $config)
    {
        $this->migration = $migration;
        $this->config = $config;
        parent::__construct('migrate');
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you to run, rollback, or refresh database migrations. You can specify "all", a plugin slug, or a specific migration file.')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Specify "all" for all migrations, a plugin slug (e.g., auth_login), or a specific migration filename (e.g., 2025-02-18_153255_users.php).'
            )
            ->addOption(
                'rollback',
                'r',
                InputOption::VALUE_NONE,
                'Rollback the last batch of migrations'
            )
            ->addOption(
                'fresh',
                'f',
                InputOption::VALUE_NONE,
                'Rollback all migrations and then run them again from scratch'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running migration command...</info>');
        
        $target = $input->getArgument('name');
        $migrationPathsToRun = [];

        try {
            if ($target === null || $target === 'all') {
                $output->writeln('<comment>Migrating all discovered paths...</comment>'); // Mesaj güncellendi
                // DÜZELTİLDİ: Tüm migration yollarını dinamik olarak al
                $migrationPathsToRun = $this->migration->getAllMigrationPaths();

                if (empty($migrationPathsToRun)) {
                    $output->writeln('<error>No migration paths found in any discovered location.</error>'); // Mesaj güncellendi
                    return Command::FAILURE;
                }

            } elseif (str_ends_with($target, '.php')) {
                $output->writeln(sprintf('<comment>Migrating specific file: %s</comment>', $target));
                $fileNameWithoutExt = basename($target, '.php');
                
                // DÜZELTİLDİ: Migration sınıfının findMigrationFile metodunu kullan
                $filePath = $this->migration->findMigrationFile($fileNameWithoutExt);
                if (!$filePath) {
                    $output->writeln(sprintf('<error>Error: Migration file "%s" not found in any discovered path.</error>', $target));
                    return Command::FAILURE;
                }
                $migrationPathsToRun[] = dirname($filePath); // run() metodu dizin beklediği için dosyanın dizinini ver

            } else {
                $output->writeln(sprintf('<comment>Migrating plugin: %s</comment>', $target));
                $pluginMigrationPath = BASE_PATH . '/plugins/' . $target . '/migrations';
                
                // DÜZELTİLDİ: Plugin'in migration klasörünün varlığını kontrol et
                if (!is_dir($pluginMigrationPath)) {
                    $output->writeln(sprintf('<error>Error: Migration directory for plugin "%s" not found at "%s".</error>', $target, $pluginMigrationPath));
                    return Command::FAILURE;
                }
                $migrationPathsToRun[] = $pluginMigrationPath;
            }

            if (empty($migrationPathsToRun)) {
                $output->writeln('<comment>No specific migrations to run based on your input.</comment>');
                return Command::SUCCESS;
            }

            // Normal migrate/rollback/fresh mantığı
            if ($input->getOption('rollback')) {
                $output->writeln('<info>Rolling back migrations...</info>');
                // Rollback için tüm yollar değil, sadece geri alınacak migration'ın yolu gerekir.
                // Migration sınıfı zaten kendi içinde findMigrationFile kullanır.
                $rolledBack = $this->migration->rollback();
                if (empty($rolledBack)) {
                    $output->writeln('<comment>No migrations to rollback.</comment>');
                } else {
                    $output->writeln(sprintf('<info>Rolled back %s migration(s).</info>', count($rolledBack)));
                    foreach ($rolledBack as $migration) {
                        $output->writeln(" - {$migration}");
                    }
                }
            } elseif ($input->getOption('fresh')) {
                $output->writeln('<info>Running fresh migrations (rollback all and then run)...</info>');
                // Fresh, tüm migration'ları silip yeniden çalıştırır, bu yüzden tüm yollar gerekir.
                $allConfiguredPaths = $this->migration->getAllMigrationPaths(); // DÜZELTİLDİ
                $migrated = $this->migration->fresh($allConfiguredPaths); 
                if (empty($migrated)) {
                    $output->writeln('<comment>No new migrations were run.</comment>');
                } else {
                    $output->writeln(sprintf('<info>Successfully ran %s migration(s).</info>', count($migrated)));
                    foreach ($migrated as $migration) {
                        $output->writeln(" - {$migration}");
                    }
                }
            } else {
                $output->writeln('<info>Running migrations...</info>');
                $migrated = $this->migration->run($migrationPathsToRun); 
                if (empty($migrated)) {
                    $output->writeln('<comment>Nothing to migrate.</comment>');
                } else {
                    $output->writeln(sprintf('<info>Successfully ran %s migration(s).</info>', count($migrated)));
                    foreach ($migrated as $migration) {
                        $output->writeln(" - {$migration}");
                    }
                }
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $output->writeln('<error>An error occurred during migration: ' . $e->getMessage() . '</error>');
            error_log('Migration CLI Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return Command::FAILURE;
        }
    }
}