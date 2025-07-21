<?php

namespace App\Core\Database;

use App\Core\Config;
use PDO;
use PDOException;
use PDOStatement; // PDOStatement sınıfını import et

/**
 * Veritabanı bağlantısını yöneten sınıf.
 * PDO kullanarak veritabanı bağlantısını kurar ve PDO nesnesini sağlar.
 */
class Connection
{
    protected ?PDO $pdo = null;
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function connect(): PDO
    {
        if ($this->pdo === null) {
            $defaultConnection = $this->config->get('database.default', 'mysql');
            $connectionConfig = $this->config->get("database.connections.{$defaultConnection}");

            if (!$connectionConfig) {
                throw new PDOException("Database connection configuration for '{$defaultConnection}' not found.");
            }

            $driver = $connectionConfig['driver'] ?? 'mysql';
            $host = $connectionConfig['host'] ?? '127.0.0.1';
            $port = $connectionConfig['port'] ?? '3306';
            $database = $connectionConfig['database'] ?? '';
            $username = $connectionConfig['username'] ?? 'root';
            $password = $connectionConfig['password'] ?? '';
            $charset = $connectionConfig['charset'] ?? 'utf8mb4';

            try {
                $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                $this->pdo = new PDO($dsn, $username, $password, $options);

            } catch (PDOException $e) {
                throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return $this->pdo;
    }

    public function getPdo(): PDO
    {
        return $this->connect();
    }

    /**
     * SQL sorgusunu çalıştırır ve sonuçları döndürür.
     *
     * @param string $sql SQL sorgusu
     * @param array $params Sorguya bağlanacak parametreler (isteğe bağlı)
     * @return array Sorgu sonuçları
     * @throws PDOException Sorgu hatası durumunda
     */
    public function query(string $sql, array $params = []): array
    {
        $pdo = $this->getPdo(); // Bağlantıyı al
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    /**
     * Bir SQL sorgusu çalıştırır (INSERT, UPDATE, DELETE) ve etkilenen satır sayısını döndürür.
     *
     * @param string $sql SQL sorgusu
     * @param array $params Sorguya bağlanacak parametreler (isteğe bağlı)
     * @return int Etkilenen satır sayısı
     * @throws PDOException Sorgu hatası durumunda
     */
    public function execute(string $sql, array $params = []): int
    {
        $pdo = $this->getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->rowCount();
    }

    /**
     * Son eklenen kaydın ID'sini döndürür.
     *
     * @return string|false
     */
    public function lastInsertId(): string|false
    {
        return $this->getPdo()->lastInsertId();
    }
}