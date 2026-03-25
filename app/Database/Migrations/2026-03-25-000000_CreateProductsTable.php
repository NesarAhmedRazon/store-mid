<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'wc_id' => [
                'type'     => 'INT',
                'unique'   => true,
                'comment'  => 'WooCommerce product ID',
            ],
            'permalink' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'sku' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'stock_quantity' => [
                'type'     => 'INT',
                'null'     => true,
                'default'  => null,
            ],
            'stock_status' => [
                'type'       => 'ENUM',
                'constraint' => ['instock', 'outofstock', 'onbackorder'],
                'default'    => 'outofstock',
            ],
            'sale_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'default'    => null,
            ],
            'regular_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
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
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('sku');
        $this->forge->createTable('products');
    }

    public function down()
    {
        $this->forge->dropTable('products');
    }
}
