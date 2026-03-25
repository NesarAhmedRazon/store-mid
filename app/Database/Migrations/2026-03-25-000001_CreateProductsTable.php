<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductsTable extends Migration
{
public function up()
{
    $this->forge->addColumn('products', [
        'wc_created_at' => [
            'type'    => 'DATETIME',
            'null'    => true,
            'default' => null,
            'after'   => 'regular_price',
        ],
        'thumb_id' => [
            'type'     => 'INT',
            'null'     => true,
            'default'  => null,
        ],
    ]);
}

public function down()
{
    $this->forge->dropColumn('products', 'wc_created_at');
}
}
