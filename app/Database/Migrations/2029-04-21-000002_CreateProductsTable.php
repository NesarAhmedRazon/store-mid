<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up()
    {
        // Rename columns
        $this->forge->modifyColumn('products', [
            'cost' => [
                'name' => 'price_buy',
                'type' => 'DECIMAL',
                'constraint' => '20,6',
                'null' => true,
                'default' => null,
                'after' => 'sku',
            ],
        ]);
        // Add price_sell column
        $this->forge->addColumn('products', [
            'price_sell' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
                'after' => 'price_buy',
            ]
        ]);
        $this->forge->modifyColumn('products', [
            'regular_price' => [
                'name' => 'price_regular',
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
                'after' => 'price_sell',
            ],
            'sale_price' => [
                'name' => 'price_offer',
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => null,
                'after' => 'price_regular',
            ],
        ]);
        
        
    }

    public function down()
    {
        // Drop price_sell column
        $this->forge->dropColumn('products', 'price_sell');
        
        // Rename columns back
        $this->forge->modifyColumn('products', [
            'price_buy' => [
                'name' => 'cost',
                'type' => 'DECIMAL',
                'constraint' => '20,6',
                'null' => true,
                'default' => null,
            ],
            'price_regular' => [
                'name' => 'regular_price',
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
            ],
            'price_offer' => [
                'name' => 'sale_price',
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => null,
            ],
        ]);
    }
}