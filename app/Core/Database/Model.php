<?php

namespace App\Core\Database;

use PDO; // PDO'yu import et
use App\Core\Database\Connection; // Connection sınıfını import et

/**
 * Temel Model sınıfı.
 * Veritabanı etkileşimleri için bir sorgu oluşturucu ve CRUD işlemleri sağlar.
 */
class Model
{
    /**
     * @var Connection Veritabanı bağlantı yöneticisi
     */
    protected Connection $db;

    /**
     * @var string Modelin ilişkili olduğu veritabanı tablosu
     */
    protected string $table;

    // Sorgu oluşturucu için instance'a özel özellikler
    protected string $select = "*";
    protected ?string $whereRawKey = null;
    protected ?array $whereRawVal = null;
    protected ?string $whereKey = null;
    protected ?array $whereVal = [];
    protected ?string $orderBy = null;
    protected ?string $limit = null;
    protected ?int $offset = null;
    protected ?string $groupBy = null;
    protected ?string $having = null;
    protected string $join = "";
    protected string $leftJoin = "";
    protected string $rightJoin = "";
    protected string $fullJoin = "";
    protected string $crossJoin = "";

    /**
     * Model sınıfının yapıcı metodu.
     * Connection nesnesini bağımlılık enjeksiyonu ile alır.
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
        // Varsayılan tablo adını sınıf adından türetme (örn: User -> users)
        if (empty($this->table)) {
            $this->table = strtolower(basename(str_replace('\\', '/', static::class))) . 's';
        }
        $this->resetQueryBuilder(); // Her yeni instance için sorgu özelliklerini sıfırla
    }

    /**
     * Sorgu oluşturucu özelliklerini sıfırlar.
     */
    protected function resetQueryBuilder(): void
    {
        $this->select = "*";
        $this->whereRawKey = null;
        $this->whereRawVal = null;
        $this->whereKey = null;
        $this->whereVal = [];
        $this->orderBy = null;
        $this->limit = null;
        $this->offset = null;
        $this->groupBy = null;
        $this->having = null;
        $this->join = "";
        $this->leftJoin = "";
        $this->rightJoin = "";
        $this->fullJoin = "";
        $this->crossJoin = "";
    }

    /**
     * Belirli bir tablo için yeni bir Model instance'ı oluşturur.
     * Statik çağrıları destekler (örn: User::table('users')).
     *
     * @param string $tableName
     * @return static
     */
    public static function table(string $tableName): static
    {
        // PHP-DI konteynerinden bir Model instance'ı almalıyız.
        // Bu, Model'in Connection bağımlılığını doğru bir şekilde almasını sağlar.
        global $app; // Application nesnesine global erişim (alternatif: IoC Container'ı statik bir metodla al)
        if (!$app instanceof \App\Core\Application) {
            throw new \RuntimeException("Application instance not available globally for static Model calls.");
        }
        /** @var static $model */
        $model = $app->getContainer()->get(static::class); // Kendi sınıfının instance'ını al
        $model->table = $tableName;
        $model->resetQueryBuilder(); // Özellikleri sıfırla
        return $model;
    }

    /**
     * SELECT ifadesini ayarlar.
     *
     * @param string|array $columns Seçilecek sütunlar
     * @return $this
     */
    public function select(string|array $columns): static
    {
        $this->select = (is_array($columns)) ? implode(",", $columns) : $columns;
        return $this;
    }

    /**
     * Ham WHERE koşulu ekler.
     *
     * @param string $whereRaw Ham SQL WHERE ifadesi (örn: "age > ? AND status = ?")
     * @param array $whereRawVal Parametre değerleri
     * @return $this
     */
    public function whereRaw(string $whereRaw, array $whereRawVal): static
    {
        $this->whereRawKey = "(" . $whereRaw . ")";
        $this->whereRawVal = $whereRawVal;
        return $this;
    }

    /**
     * WHERE koşulu ekler.
     *
     * @param string|array $column Sütun adı veya koşul dizisi
     * @param mixed $operator Operatör (=, >, <, LIKE vb.) veya değer
     * @param mixed $value Değer (eğer operatör belirtilmişse)
     * @return $this
     */
    public function where(string|array $column, mixed $operator = null, mixed $value = null): static
    {
        if (is_array($column)) {
            $keyList = [];
            foreach ($column as $key => $val) {
                $this->whereVal[] = $val;
                $keyList[] = $key;
            }
            $this->whereKey = implode("=? AND ", $keyList) . "=?";
        } else {
            // where('id', 1) veya where('name', '=', 'John')
            if ($value === null && $operator !== null) { // where('id', 1)
                $this->whereVal[] = $operator;
                $this->whereKey = $column . " = ?";
            } elseif ($value !== null) { // where('name', '=', 'John')
                $this->whereVal[] = $value;
                $this->whereKey = $column . " " . $operator . " ?";
            } else {
                // Hatalı kullanım
                throw new \InvalidArgumentException("Invalid where clause arguments.");
            }
        }
        return $this;
    }

    /**
     * ORDER BY ifadesini ayarlar.
     *
     * @param string $column Sütun adı
     * @param string $direction Yön (ASC veya DESC)
     * @return $this
     */
    public function orderBy(string $column, string $direction = "ASC"): static
    {
        $this->orderBy = $column . " " . strtoupper($direction);
        return $this;
    }

    /**
     * LIMIT ifadesini ayarlar.
     *
     * @param int $limit Sınır
     * @param int|null $offset Ofset (isteğe bağlı)
     * @return $this
     */
    public function limit(int $limit, ?int $offset = null): static
    {
        $this->limit = (string)$limit;
        if ($offset !== null) {
            $this->offset = $offset; // Offset ayrı bir özellik olarak tutulur
        }
        return $this;
    }

    /**
     * OFFSET ifadesini ayarlar.
     *
     * @param int $offset Ofset
     * @return $this
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * GROUP BY ifadesini ayarlar.
     *
     * @param string $column Sütun adı
     * @return $this
     */
    public function groupBy(string $column): static
    {
        $this->groupBy = $column;
        return $this;
    }

    /**
     * HAVING koşulu ekler.
     *
     * @param string $condition Koşul
     * @return $this
     */
    public function having(string $condition): static
    {
        $this->having = $condition;
        return $this;
    }

    /**
     * INNER JOIN ekler.
     *
     * @param string $tableName Katılacak tablo adı
     * @param string $thisColumn Bu tablodaki sütun
     * @param string $joinColumn Katılacak tablodaki sütun
     * @return $this
     */
    public function join(string $tableName, string $thisColumn, string $joinColumn): static
    {
        $this->join .= "INNER JOIN " . $tableName . " ON " . $this->table . "." . $thisColumn . "=" . $tableName . "." . $joinColumn . " ";
        return $this;
    }

    /**
     * LEFT JOIN ekler.
     *
     * @param string $tableName
     * @param string $thisColumn
     * @param string $joinColumn
     * @return $this
     */
    public function leftJoin(string $tableName, string $thisColumn, string $joinColumn): static
    {
        $this->leftJoin .= "LEFT JOIN " . $tableName . " ON " . $this->table . "." . $thisColumn . "=" . $tableName . "." . $joinColumn . " ";
        return $this;
    }

    /**
     * RIGHT JOIN ekler.
     *
     * @param string $tableName
     * @param string $thisColumn
     * @param string $joinColumn
     * @return $this
     */
    public function rightJoin(string $tableName, string $thisColumn, string $joinColumn): static
    {
        $this->rightJoin .= "RIGHT JOIN " . $tableName . " ON " . $this->table . "." . $thisColumn . "=" . $tableName . "." . $joinColumn . " ";
        return $this;
    }

    /**
     * FULL JOIN ekler.
     *
     * @param string $tableName
     * @param string $thisColumn
     * @param string $joinColumn
     * @return $this
     */
    public function fullJoin(string $tableName, string $thisColumn, string $joinColumn): static
    {
        $this->fullJoin .= "FULL JOIN " . $tableName . " ON " . $this->table . "." . $thisColumn . "=" . $tableName . "." . $joinColumn . " ";
        return $this;
    }

    /**
     * CROSS JOIN ekler.
     *
     * @param string $tableName
     * @return $this
     */
    public function crossJoin(string $tableName): static
    {
        $this->crossJoin .= "CROSS JOIN " . $tableName;
        return $this;
    }

    /**
     * Sorguyu çalıştırır ve tüm sonuçları döndürür.
     *
     * @return array
     */
    public function get(): array
    {
        $SQL = "SELECT " . $this->select . " FROM " . $this->table . " ";
        $SQL .= (!empty($this->join)) ? $this->join : "";
        $SQL .= (!empty($this->leftJoin)) ? $this->leftJoin : "";
        $SQL .= (!empty($this->rightJoin)) ? $this->rightJoin : "";
        $SQL .= (!empty($this->fullJoin)) ? $this->fullJoin : "";
        $SQL .= (!empty($this->crossJoin)) ? $this->crossJoin : "";

        $params = [];
        $whereClauses = [];

        if (!empty($this->whereKey)) {
            $whereClauses[] = $this->whereKey;
            $params = array_merge($params, $this->whereVal);
        }
        if (!empty($this->whereRawKey)) {
            $whereClauses[] = $this->whereRawKey;
            $params = array_merge($params, $this->whereRawVal);
        }

        if (!empty($whereClauses)) {
            $SQL .= "WHERE " . implode(" AND ", $whereClauses) . " ";
        }

        if (!empty($this->groupBy)) {
            $SQL .= "GROUP BY " . $this->groupBy . " ";
            if (!empty($this->having)) {
                $SQL .= "HAVING " . $this->having . " ";
            }
        }

        $SQL .= (!empty($this->orderBy)) ? "ORDER BY " . $this->orderBy . " " : "";
        $SQL .= (!empty($this->limit)) ? "LIMIT " . $this->limit : "";
        $SQL .= (!empty($this->offset)) ? " OFFSET " . $this->offset : ""; // OFFSET'in başına boşluk ekledim

        $results = $this->db->query($SQL, $params);
        $this->resetQueryBuilder(); // Sorgu bittikten sonra özellikleri sıfırla
        return $results;
    }

    /**
     * Sorguyu çalıştırır ve ilk sonucu döndürür.
     *
     * @return array|false
     */
    public function first(): array|false
    {
        $rows = $this->limit(1)->get(); // Limit 1 ekleyip get() çağır
        return $rows ? $rows[0] : false;
    }

    /**
     * Veritabanına yeni bir kayıt ekler.
     *
     * @param array $data Eklenecek veriler (sütun_adı => değer)
     * @param bool $returnLastInsertId Son eklenen ID'yi döndürsün mü?
     * @return bool|string True/False veya son eklenen ID
     */
    public function addCreate(array $data, bool $returnLastInsertId = false): bool|string
    {
        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $values = array_values($data);

        $SQL = "INSERT INTO " . $this->table . " (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";

        $this->db->execute($SQL, $values); // execute metodu rowCount döndürür

        if ($returnLastInsertId) {
            return $this->db->lastInsertId();
        }
        return true; // Başarılı ekleme
    }

    /**
     * Veritabanındaki kayıtları günceller.
     *
     * @param array $data Güncellenecek veriler (sütun_adı => değer)
     * @return bool
     */
    public function update(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $setParts = [];
        $updateValues = [];
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $updateValues[] = $value;
        }

        $SQL = "UPDATE " . $this->table . " SET " . implode(", ", $setParts) . " ";

        $params = [];
        $whereClauses = [];

        if (!empty($this->whereKey)) {
            $whereClauses[] = $this->whereKey;
            $params = array_merge($params, $this->whereVal);
        }
        if (!empty($this->whereRawKey)) {
            $whereClauses[] = $this->whereRawKey;
            $params = array_merge($params, $this->whereRawVal);
        }

        if (!empty($whereClauses)) {
            $SQL .= "WHERE " . implode(" AND ", $whereClauses) . " ";
        } else {
            // WHERE koşulu yoksa UPDATE yapma (güvenlik için)
            // Ya da tüm tabloyu güncellemek istediğinizi varsayarak devam edebilirsiniz.
            // Bu genellikle tehlikelidir.
            error_log("WARNING: Attempted to update table '{$this->table}' without a WHERE clause.");
            $this->resetQueryBuilder();
            return false; // Veya bir exception fırlat
        }

        $finalParams = array_merge($updateValues, $params);
        $rowCount = $this->db->execute($SQL, $finalParams);
        $this->resetQueryBuilder();
        return $rowCount > 0;
    }

    /**
     * Veritabanındaki kayıtları siler.
     *
     * @return bool
     */
    public function delete(): bool
    {
        $SQL = "DELETE FROM " . $this->table . " ";

        $params = [];
        $whereClauses = [];

        if (!empty($this->whereKey)) {
            $whereClauses[] = $this->whereKey;
            $params = array_merge($params, $this->whereVal);
        }
        if (!empty($this->whereRawKey)) {
            $whereClauses[] = $this->whereRawKey;
            $params = array_merge($params, $this->whereRawVal);
        }

        if (!empty($whereClauses)) {
            $SQL .= "WHERE " . implode(" AND ", $whereClauses) . " ";
        } else {
            // WHERE koşulu yoksa DELETE yapma (güvenlik için)
            error_log("WARNING: Attempted to delete from table '{$this->table}' without a WHERE clause.");
            $this->resetQueryBuilder();
            return false; // Veya bir exception fırlat
        }

        $rowCount = $this->db->execute($SQL, $params);
        $this->resetQueryBuilder();
        return $rowCount > 0;
    }
}