<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductAttributeValuesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'SMALLINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'wc_id' => [
                'type' => 'SMALLINT',
                'unsigned' => true,
                'unique' => true,
            ],
            'attribute_id' => [
                'type' => 'SMALLINT',
                'unsigned' => true,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'sort_order' => [
                'type' => 'SMALLINT',
                'unsigned' => true,
                'default' => 0,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('attribute_id');
        $this->forge->addKey('slug');

        $this->forge->addForeignKey(
            'attribute_id',
            'product_attributes',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('product_attribute_values', true, [
            'ENGINE' => 'InnoDB'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('product_attribute_values', true);
    }
}