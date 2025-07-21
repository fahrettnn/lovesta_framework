<?php

namespace MyNewTestPlugin\Migrations; // Plugin adınıza göre namespace değişebilir

use App\Core\Database\Migration\AbstractMigration;

class CreateTestProductsTable extends AbstractMigration // Sınıf adı dosya adından türetildi
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS tbl_products (
            product_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->executeSql($sql);
        echo "Created 'tbl_products' table.\n";
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS tbl_products";
        $this->executeSql($sql);
        echo "Dropped 'tbl_products' table.\n";
    }
}