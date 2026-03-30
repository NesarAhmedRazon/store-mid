<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductAttributeMapTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],

            // ✅ MATCHES products.id (INT)
            'product_id' => [
                'type' => 'INT',
                'unsigned' => false, // IMPORTANT: matches your products table
            ],

            'attribute_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],

            'value_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'order' => [
                'type' => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],

            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);

        $this->forge->addUniqueKey([
            'product_id',
            'attribute_id',
            'value_id'
        ]);

        $this->forge->addKey('product_id');
        $this->forge->addKey('value_id');

        $this->forge->addForeignKey(
            'product_id',
            'products',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'attribute_id',
            'product_attributes',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'value_id',
            'product_attribute_values',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('product_attribute_map');
    }

    public function down()
    {
        $this->forge->dropTable('product_attribute_map', true);
    }
}