<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProductCountToCategories extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('product_categories', [
            'product_count' => [
                'type'       => 'INT',
                'default'    => 0,
                'null'       => false,
                'comment'    => 'Direct product count (not including descendants). Updated on import/sync.',
                'after'      => 'sort_order',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('product_categories', 'product_count');
    }
}
