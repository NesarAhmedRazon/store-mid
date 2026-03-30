<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AttributeProductMapCreate extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'product_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'attribute_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'value_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'order' => [
                'type'    => 'INT',
                'null'    => true,
                'default' => null,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);

        $this->forge->addUniqueKey([
            'product_id',
            'attribute_id',
            'value_id',
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

        $this->forge->createTable('product_attribute_map', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('product_attribute_map', true);
    }
}