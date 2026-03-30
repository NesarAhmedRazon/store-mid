<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AttributesProductTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'wc_id' => [
                'type'   => 'INT',
                'unique' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'is_public' => [
                'type'       => 'INT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'sort_order' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('name');

        $this->forge->createTable('product_attributes', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('product_attributes', true);
    }
}