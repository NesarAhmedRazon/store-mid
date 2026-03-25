<?php

/**
 * CREATE TABLE orders (
 *   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
*
  *  wc_order_id BIGINT UNSIGNED NOT NULL,
  *  wc_products JSON NOT NULL,
  *  wc_total DECIMAL(12,2) NOT NULL,
*
   * created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
   * updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
*
   * UNIQUE KEY uniq_wc_order_id (wc_order_id)
* );
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrderTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'wc_order_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'unique' => true,
                'null' => true
            ],
            'wc_products' => [
                'type' => 'JSON',
                'null' => true
            ],
            'wc_total' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true
            ],
            
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['new', 'confirmed', 'pending', 'processing', 'ready-to-shipping', 'completed', 'cancelled'],
                'default' => 'pending'
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('orders');
    }

    public function down()
    {
        $this->forge->dropTable('orders');
    }
}