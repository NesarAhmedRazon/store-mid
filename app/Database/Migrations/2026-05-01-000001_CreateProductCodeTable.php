<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductCodeTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'product_id' => [
                'type'    => 'INT',
                'null'    => false,
                'comment' => 'FK → products.id',
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'language' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'file_dir' => [ // <- added
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'code' => [
                'type'    => 'LONGTEXT',
                'null'    => false,
                'comment' => 'The actual source code',
            ],
            'editor_theme' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'vs-dark',
            ],
            'sort_order' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('programming', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('programming', true);
    }
}
