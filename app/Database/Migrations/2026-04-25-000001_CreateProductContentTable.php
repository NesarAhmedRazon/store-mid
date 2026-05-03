<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * product_content — stores rich Elementor HTML + scoped CSS per product.
 *
 * Kept in a separate table (not columns on products) because:
 *  - Content is large (50–100 KB+) and only needed on single-product views
 *  - Keeping it separate means list queries never touch this data
 *  - One-to-one with products — JOIN only when needed
 *
 * The css column stores the Elementor-generated stylesheet scoped to the
 * product's elementor ID (e.g. .elementor-10836 { ... }).
 * The html column stores the full rendered Elementor HTML markup.
 */
class CreateProductContentTable extends Migration
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
            'html' => [
                'type'    => 'LONGTEXT',
                'null'    => true,
                'default' => null,
                'comment' => 'Elementor-rendered HTML content',
            ],
            'css' => [
                'type'    => 'LONGTEXT',
                'null'    => true,
                'default' => null,
                'comment' => 'Elementor-generated scoped CSS',
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('product_id'); // one row per product
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('product_content', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('product_content', true);
    }
}
