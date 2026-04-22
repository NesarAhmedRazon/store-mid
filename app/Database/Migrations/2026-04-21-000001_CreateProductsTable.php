<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('products', [
            'stock_unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'stock_status', // MySQL only
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('products', 'stock_unit');
    }
}