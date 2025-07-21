<?php

namespace Home\Migrations\Migrations;

use App\Core\Database\Migration\AbstractMigration;

class Home extends AbstractMigration
{
    public function up(): void
    {
        // Örnek kullanım:
        // \$this->addColumn('id INT AUTO_INCREMENT PRIMARY KEY');
        // \$this->addColumn('name VARCHAR(255) NOT NULL');
        // \$this->createTable('home_table'); // YENİ: Tablo adı placeholder'ı
        // echo "Created 'home_table' table.\\n";
    }

    public function down(): void
    {
        // Örnek kullanım:
        // \$this->dropTable('home_table'); // YENİ: Tablo adı placeholder'ı
        // echo "Dropped 'home_table' table.\\n";
    }
}