<?php

namespace App\Core\Database;

use PDO;
use PDOException;
use App\Core\Config;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Core\Database\Migration\AbstractMigration;

/**
 * Veritabanı migration'larını yöneten sınıf.
 * Veritabanı şema değişikliklerini uygular ve geri alır.
 */
class Migration
{
    protected PDO $pdo;
    protected Config $config;
    protected Logger $logger;
    protected array $namespaceMap = [];

    public function __construct(Connection $connection, Config $config)
    {
        $this->pdo = $connection->getPdo();
        $this->config = $config;
        $this->logger = new Logger('migrations');
        $this->logger->pushHandler(new StreamHandler(BASE_PATH . '/storage/logs/migrations.log', Logger::INFO));

        $this->ensureMigrationsTableExists();
    }

    /**
     * Belirtilen migration yollarını ve ilgili namespace'leri RobotLoader için hazırlar.
     * Bu metod, run/rollback/fresh çağrılmadan önce çağrılmalı.
     * @param array $paths Mutlak migration yolları
     */
    protected function buildNamespaceMapForPaths(array $paths): void
    {
        $this->namespaceMap = []; // Her çağrıda haritayı sıfırla
        foreach ($paths as $absolutePath) {
            $relativePath = str_replace(BASE_PATH . '/', '', $absolutePath);
            $fullNamespace = '';

            if (str_starts_with($relativePath, 'plugins/')) {
                $parts = explode('/', $relativePath);
                $pluginSlug = $parts[1];

                $cleanedSlug = str_replace('_', ' ', $pluginSlug);
                $capitalizedSlug = ucwords($cleanedSlug);
                $finalPluginNamespacePart = str_replace(' ', '', $capitalizedSlug);

                $fullNamespace = $finalPluginNamespacePart . '\\Migrations';
            } else if (str_starts_with($relativePath, 'database/migrations')) {
                $fullNamespace = 'App\\Database\\Migrations';
            } else {
                $pathParts = explode('/', $relativePath);
                $namespacedParts = array_map(function($part) {
                    return str_replace(' ', '', ucwords(str_replace('_', ' ', $part)));
                }, $pathParts);
                $fullNamespace = 'App\\' . implode('\\', $namespacedParts);
            }

            $this->namespaceMap[$relativePath] = $fullNamespace;
        }
    }

    protected function ensureMigrationsTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL,
            migrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        try {
            $this->pdo->exec($sql);
            $this->logger->info("Ensured 'migrations' table exists.");
        } catch (PDOException $e) {
            $this->logger->error("Failed to create 'migrations' table: " . $e->getMessage());
            throw new PDOException("Failed to create 'migrations' table: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Belirtilen dizinlerdeki bekleyen migration'ları çalıştırır.
     * @param string|array $paths Migration dosyalarının bulunduğu mutlak dizin(ler)
     * @return array Çalıştırılan migration'ların listesi
     */
    public function run(string|array $paths): array
    {
        if (!is_array($paths)) {
            $paths = [$paths];
        }

        $this->buildNamespaceMapForPaths($paths); // Çalıştırılacak yollar için namespace haritasını oluştur

        $ranMigrations = $this->getRanMigrations();
        $filesToRun = [];

        foreach ($paths as $absolutePath) {
            if (!is_dir($absolutePath)) {
                $this->logger->warning("Migration path not found: " . $absolutePath);
                continue;
            }
            $migrationFiles = glob($absolutePath . '/*.php');

            foreach ($migrationFiles as $file) {
                $fileName = basename($file, '.php');
                if (!in_array($fileName, $ranMigrations)) {
                    $filesToRun[$fileName] = $file;
                }
            }
        }

        ksort($filesToRun);
        $runCount = 0;
        $batch = $this->getNextBatchNumber();
        $migrated = [];

        foreach ($filesToRun as $fileName => $filePath) {
            
            require_once $filePath;

            $shortClassName = $this->getClassNameFromFileName($fileName);
            
            $relativePath = str_replace(BASE_PATH . '/', '', dirname($filePath)); 

            $namespace = $this->namespaceMap[$relativePath] ?? null;

            if ($namespace === null) {
                $this->logger->error("Migration: No namespace mapped for path '{$relativePath}'. Cannot run '{$fileName}'.");
                continue;
            }

            $fullClassName = $namespace . '\\' . $shortClassName;
            

            if (!class_exists($fullClassName)) {
                $this->logger->error("Migration class not found: {$fullClassName} in {$filePath}. Check namespace/class name.");
                continue;
            }

            try {
                /** @var \App\Core\Database\Migration\AbstractMigration $instance */
                $instance = new $fullClassName($this->pdo);

                if (!method_exists($instance, 'up')) {
                    $this->logger->error("Migration class {$fullClassName} does not have an 'up' method.");
                    continue;
                }

                $instance->up();
                $this->logMigration($fileName, $batch);
                $this->logger->info("Migrated: {$fileName} (Batch: {$batch})");
                $migrated[] = $fileName;
                $runCount++;
            } catch (\Throwable $e) {
                $this->logger->error("Error migrating {$fileName}: " . $e->getMessage());
                throw new PDOException("Error migrating {$fileName}: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        if ($runCount === 0) {
            $this->logger->info("Nothing to migrate.");
        } else {
            $this->logger->info("Successfully migrated {$runCount} files in batch {$batch}.");
        }
        return $migrated;
    }

    /**
     * Son migration grubunu (batch) geri alır.
     * @return array Geri alınan migration'ların listesi
     */
    public function rollback(): array
    {
        $lastBatch = $this->getCurrentBatchNumber();
        if ($lastBatch === 0) {
            $this->logger->info("No migrations to rollback.");
            return [];
        }

        $migrationsToRollback = $this->getMigrationsInBatch($lastBatch);
        $rolledBack = [];

        // Rollback edilecek migration'ların yollarını bul ve namespace haritasını oluştur
        $pathsToRollback = [];
        foreach ($migrationsToRollback as $fileName => $data) {
            $filePath = $this->findMigrationFile($fileName);
            if ($filePath) {
                $pathsToRollback[] = dirname($filePath);
            }
        }
        $this->buildNamespaceMapForPaths(array_unique($pathsToRollback));

        krsort($migrationsToRollback);

        foreach ($migrationsToRollback as $fileName => $data) {
            $filePath = $this->findMigrationFile($fileName);
            if (!$filePath) {
                $this->logger->warning("Migration file not found for rollback: {$fileName}");
                continue;
            }

            require_once $filePath;
            $shortClassName = $this->getClassNameFromFileName($fileName);
            $relativePath = str_replace(BASE_PATH . '/', '', dirname($filePath));
            $namespace = $this->namespaceMap[$relativePath] ?? null;
            $fullClassName = $namespace ? $namespace . '\\' . $shortClassName : $shortClassName;

            if (!class_exists($fullClassName)) {
                $this->logger->error("Migration class not found for rollback: {$fullClassName} in {$filePath}");
                continue;
            }

            try {
                /** @var \App\Core\Database\Migration\AbstractMigration $instance */
                $instance = new $fullClassName($this->pdo);

                if (!method_exists($instance, 'down')) {
                    $this->logger->warning("Migration class {$fullClassName} does not have a 'down' method. Skipping rollback.");
                    continue;
                }

                $instance->down();
                $this->removeMigrationLog($fileName);
                $this->logger->info("Rolled back: {$fileName} (Batch: {$lastBatch})");
                $rolledBack[] = $fileName;
            } catch (\Throwable $e) {
                $this->logger->error("Error rolling back {$fileName}: " . $e->getMessage());
                throw new PDOException("Error rolling back {$fileName}: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        if (empty($rolledBack)) {
            $this->logger->info("No migrations rolled back in batch {$lastBatch}.");
        } else {
            $this->logger->info("Successfully rolled back " . count($rolledBack) . " files in batch {$lastBatch}.");
        }
        return $rolledBack;
    }

    public function fresh(string|array $paths): array
    {
        $this->logger->info("Running fresh migration...");
        $this->rollbackAll();
        return $this->run($paths);
    }

    protected function rollbackAll(): void
    {
        $this->logger->info("Rolling back all migrations...");
        while ($this->getCurrentBatchNumber() > 0) {
            $this->rollback();
        }
        $this->logger->info("All migrations rolled back.");
    }

    protected function getRanMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY batch ASC, migration ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    protected function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM migrations");
        $maxBatch = (int)$stmt->fetchColumn();
        return $maxBatch + 1;
    }

    protected function getCurrentBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM migrations");
        return (int)$stmt->fetchColumn();
    }

    protected function getMigrationsInBatch(int $batchNumber): array
    {
        $stmt = $this->pdo->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY migration DESC");
        $stmt->execute([$batchNumber]);
        return array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    protected function logMigration(string $migrationName, int $batch): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
        $stmt->execute([$migrationName, $batch]);
    }

    protected function removeMigrationLog(string $migrationName): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$migrationName]);
    }

    /**
     * Dosya adından sınıf adını çıkarır.
     * Örn: "2025-02-18_153255_users" -> "Users"
     * Bu metod, dosya adının baştaki tarih ve saat kısmını regex ile kaldırarak sınıf adını bulur.
     *
     * @param string $fileName (örn: "2025-02-18_153255_users") - basename'den geldiği için .php uzantısı yok
     * @return string
     */
    protected function getClassNameFromFileName(string $fileName): string
    {
        // Bu regex, dosya adının başındaki tarih (YYYY-MM-DD) ve saat (HHMMSS) prefix'ini ve son alt çizgiyi kaldırır.
        // Geriye sadece sınıf adı kalır (örn: "users" veya "my_new_table").
        $className = preg_replace('/^\d{4}-\d{2}-\d{2}_\d{6}_/', '', $fileName);
        
        // Eğer sınıf adında hala alt çizgi varsa, bunları kaldırıp kelimeleri birleştir.
        // Örn: "my_new_table" -> "MyNewTable"
        $className = str_replace('_', '', ucwords($className, '_'));

        // Son olarak, ilk harfi büyüt (eğer birden fazla kelime yoksa veya ucwords tarafından zaten yapılmadıysa)
        $className = ucfirst($className);

        return $className;
    }

    /**
     * Belirtilen migration dosyasının tam yolunu bulur.
     * @param string $fileName Migration dosyasının adı (uzantısız)
     * @return string|false Dosyanın tam yolu veya bulunamazsa false
     */
    public function findMigrationFile(string $fileName): string|false // GÖRÜNÜRLÜĞÜ PUBLIC YAPILDI
    {
        // YENİ: Tüm plugin migration yollarını ve ana database migration yolunu dinamik olarak bul
        $allPossibleMigrationPaths = $this->getAllMigrationPaths();

        foreach ($allPossibleMigrationPaths as $path) {
            $filePath = $path . '/' . $fileName . '.php'; // path zaten mutlak yol
            if (file_exists($filePath)) {
                return $filePath;
            }
        }
        return false;
    }

    /**
     * Tüm eklentilerin ve ana uygulamanın migration dizinlerinin mutlak yollarını döndürür.
     * @return array
     */
    public function getAllMigrationPaths(): array // Visibility changed to PUBLIC
    {
        $paths = [];

        // Ana uygulama migration yolu
        $mainAppMigrationPath = BASE_PATH . '/database/migrations';
        if (is_dir($mainAppMigrationPath)) {
            $paths[] = $mainAppMigrationPath;
        }

        // Tüm eklentilerin migration yolları
        $pluginsDir = BASE_PATH . '/plugins';
        if (is_dir($pluginsDir)) {
            $pluginSlugs = glob($pluginsDir . '/*', GLOB_ONLYDIR);
            foreach ($pluginSlugs as $pluginPath) {
                $pluginMigrationPath = $pluginPath . '/migrations';
                if (is_dir($pluginMigrationPath)) {
                    $paths[] = $pluginMigrationPath;
                }
            }
        }
        return $paths;
    }
}