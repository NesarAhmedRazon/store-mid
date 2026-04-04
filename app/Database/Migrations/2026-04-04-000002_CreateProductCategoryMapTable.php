<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductCategoryMapTable extends Migration
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

            'category_id' => [
                'type' => 'INT',
                'null' => false,
            ],

            // Marks the single "primary" category shown in breadcrumbs
            // A product may belong to many categories but has one primary
            'is_primary' => [
                'type'       => 'INT',
                'constraint' => 1,
                'default'    => 0,
            ],

            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);

        // A product can only be linked to the same category once
        $this->forge->addUniqueKey(['product_id', 'category_id']);

        $this->forge->addKey('category_id');    // fast "all products in category" lookup
        $this->forge->addKey('product_id');     // fast "all categories for product" lookup

        $this->forge->addForeignKey(
            'product_id',
            'products',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'category_id',
            'product_categories',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('product_category_map', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('product_category_map', true);
    }
}
