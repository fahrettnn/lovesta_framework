<?php

namespace App\Core\Database\Migration;

use PDO;
use PDOException;

/**
 * Tüm veritabanı migration sınıfları için soyut temel sınıf.
 * Her migration, 'up' ve 'down' metotlarını uygulamalıdır.
 * Bu sınıf, tablo oluşturma ve veri ekleme için yardımcı metotlar sağlar.
 */
abstract class AbstractMigration
{
    /**
     * @var PDO Veritabanı bağlantı nesnesi
     */
    protected PDO $pdo;

    // Tablo oluşturucu için geçici özellikler
    private array $columns = [];
    private array $keys = [];
    private array $primaryKeys = [];
    private array $foreignKeys = [];
    private array $uniqueKeys = [];
    private array $fullTextKeys = [];
    private array $data = []; // Insert edilecek veriler için

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Migration'ı uygular (veritabanı şemasını değiştirir).
     * Bu metot, migration çalıştırıldığında çağrılır.
     */
    abstract public function up(): void;

    /**
     * Migration'ı geri alır (veritabanı şemasını önceki haline döndürür).
     * Bu metot, migration geri alındığında çağrılır.
     */
    abstract public function down(): void;

    /**
     * SQL sorgusunu çalıştırır.
     * @param string $sql
     * @param array $params Parametreler (isteğe bağlı)
     * @return int Etkilenen satır sayısı
     * @throws PDOException
     */
    protected function executeSql(string $sql, array $params = []): int
    {
        try {
            if (empty($params)) {
                return $this->pdo->exec($sql);
            } else {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->rowCount();
            }
        } catch (PDOException $e) {
            // Hata durumunda loglama veya daha spesifik hata yönetimi eklenebilir.
            throw new PDOException("SQL execution failed: " . $e->getMessage() . "\nSQL: " . $sql, (int)$e->getCode(), $e);
        }
    }

    // --- Tablo Oluşturma Yardımcı Metotları ---

    /**
     * Bir sütun tanımı ekler (örn: 'id INT AUTO_INCREMENT').
     * @param string $columnDef Sütun tanımı
     * @return $this
     */
    public function addColumn(string $columnDef): static
    {
        $this->columns[] = $columnDef;
        return $this;
    }

    /**
     * Normal bir anahtar (index) ekler.
     * @param string $key Anahtar adı
     * @return $this
     */
    public function addKey(string $key): static
    {
        $this->keys[] = $key;
        return $this;
    }

    /**
     * Primary Key ekler.
     * @param string $primaryKey Primary Key sütunu(ları)
     * @return $this
     */
    public function addPrimaryKey(string $primaryKey): static
    {
        $this->primaryKeys[] = $primaryKey;
        return $this;
    }

    /**
     * Unique Key ekler.
     * @param string $key Unique Key sütunu(ları)
     * @return $this
     */
    public function addUniqueKey(string $key): static
    {
        $this->uniqueKeys[] = $key;
        return $this;
    }

    /**
     * Full Text Key ekler.
     * @param string $key Full Text Key sütunu(ları)
     * @return $this
     */
    public function addFullTextKey(string $key): static
    {
        $this->fullTextKeys[] = $key;
        return $this;
    }

    /**
     * Foreign Key ekler.
     * @param string $column Kendi tablonuzdaki sütun
     * @param string $referencedTable Referans alınan tablo
     * @param string $referencedColumn Referans alınan tablodaki sütun
     * @return $this
     */
    public function addForeignKey(string $column, string $referencedTable, string $referencedColumn): static
    {
        $this->foreignKeys[] = "($column) REFERENCES $referencedTable($referencedColumn)";
        return $this;
    }

    /**
     * Tablo oluşturma sorgusunu çalıştırır.
     * @param string $table Oluşturulacak tablo adı
     */
    public function createTable(string $table): void
    {
        if (empty($this->columns)) {
            throw new PDOException("Column data not found! Could not create table: " . $table);
        }

        $query = "CREATE TABLE IF NOT EXISTS $table (";
        $query .= implode(",", $this->columns);

        foreach ($this->primaryKeys as $key) {
            $query .= ", primary key ($key)";
        }
        foreach ($this->keys as $key) {
            $query .= ", key ($key)";
        }
        foreach ($this->uniqueKeys as $key) {
            $query .= ", unique key ($key)";
        }
        foreach ($this->fullTextKeys as $key) {
            $query .= ", fulltext key ($key)";
        }
        foreach ($this->foreignKeys as $key) {
            $query .= ", foreign key $key";
        }

        $query .= ")ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->executeSql($query);
        
        // Özellikleri sıfırla
        $this->columns = [];
        $this->keys = [];
        $this->primaryKeys = [];
        $this->foreignKeys = [];
        $this->uniqueKeys = [];
        $this->fullTextKeys = [];
    }

    /**
     * Tabloya veri eklemek için bir satır ekler.
     * @param array $data Eklenecek veri dizisi (örn: ['column' => 'value'])
     * @return $this
     */
    public function addData(array $data): static
    {
        $this->data[] = $data;
        return $this;
    }

    /**
     * Toplanan verileri tabloya toplu olarak ekler.
     * @param string $table Veri eklenecek tablo adı
     */
    public function insert(string $table): void
    {
        if (empty($this->data)) {
            return; // No data to insert
        }

        foreach ($this->data as $row) {
            $keys = array_keys($row);
            $columns_string = implode(",", $keys);
            $values_string = ':'.implode(",:", $keys); // Prepared statement için placeholders

            $query = "INSERT INTO $table ($columns_string) VALUES ($values_string)";
            
            // Prepared statement için değerleri eşleştir
            $boundValues = [];
            foreach($row as $key => $value) {
                $boundValues[":".$key] = $value;
            }

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($boundValues);
        }

        $this->data = []; // Verileri sıfırla
    }

    /**
     * Tabloyu siler.
     * @param string $table Silinecek tablo adı
     */
    public function dropTable(string $table): void
    {
        $query = "DROP TABLE IF EXISTS $table ";
        $this->executeSql($query);
    }
}