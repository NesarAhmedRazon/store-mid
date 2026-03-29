<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductsTable extends Migration
{
public function up()
{
    $this->forge->addColumn('products', [
        'cost' => [
                'type'       => 'DECIMAL',
                'constraint' => '20,6',
                'null'       => true,
                'default'    => null,
            ],
    ]);
}

public function down()
{
    $this->forge->dropColumn('products', 'cost');
}
}
