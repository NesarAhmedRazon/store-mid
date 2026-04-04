<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductCategoriesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],

            // WooCommerce term ID — null for locally created categories
            'wc_id' => [
                'type'    => 'INT',
                'unique'  => true,
                'null'    => true,
                'default' => null,
                'comment' => 'WooCommerce term_id',
            ],

            // Adjacency list — null means root category
            'parent_id' => [
                'type'    => 'INT',
                'null'    => true,
                'default' => null,
            ],

            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],

            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 140,
                'unique'     => true,
            ],

            'description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
            ],

            // Materialized path — stores ancestor chain as "1/5/12"
            // Root category: path = "1"
            // Enables subtree queries:  WHERE path LIKE '1/5/%'
            // Enables ancestor queries: parse the string, IN (1, 5, 12)
            'path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'comment'    => 'Ancestor chain e.g. 1/5/12 — root to self',
            ],

            // Depth cached from path — 0 = root, 1 = child, 2 = grandchild…
            // Avoids counting slashes in path on every query
            'depth' => [
                'type'    => 'TINYINT',
                'default' => 0,
            ],

            // Thumbnail — FK to media.id
            'thumb_id' => [
                'type'    => 'INT',
                'null'    => true,
                'default' => null,
            ],

            'sort_order' => [
                'type'    => 'INT',
                'default' => 0,
            ],

            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('parent_id');
        // 'slug' and 'wc_id' are already indexed via 'unique' => true on the field — no addKey() needed
        $this->forge->addKey('path');        // prefix scans on LIKE 'x/%'
        $this->forge->addKey('depth');       // filter by level fast

        $this->forge->addForeignKey(
            'parent_id',
            'product_categories',
            'id',
            'RESTRICT',   // prevent deleting a parent that still has children
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'thumb_id',
            'media',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->forge->createTable('product_categories', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('product_categories', true);
    }
}
