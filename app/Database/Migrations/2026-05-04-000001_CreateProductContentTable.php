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
       $this->forge->addColumn('product_content', [            
            'js' => [
                'type'    => 'LONGTEXT',
                'null'    => true,
                'default' => null,
                'comment' => 'Elementor-generated JS',
            ],
        ]);

    }

    public function down(): void
    {
        $this->forge->dropColumn('product_content', 'price_sell');
    }
}
