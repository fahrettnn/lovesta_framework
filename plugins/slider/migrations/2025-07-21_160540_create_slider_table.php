<?php

namespace Slider\Migrations\Migrations;

use App\Core\Database\Migration\AbstractMigration;

class Slider extends AbstractMigration
{
    public function up(): void
    {
        // Örnek kullanım:
        // \$this->addColumn('id INT AUTO_INCREMENT PRIMARY KEY');
        // \$this->addColumn('name VARCHAR(255) NOT NULL');
        // \$this->createTable('slider_table'); // YENİ: Tablo adı placeholder'ı
        // echo "Created 'slider_table' table.\\n";
    }

    public function down(): void
    {
        // Örnek kullanım:
        // \$this->dropTable('slider_table'); // YENİ: Tablo adı placeholder'ı
        // echo "Dropped 'slider_table' table.\\n";
    }
}