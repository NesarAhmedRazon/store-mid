<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterProductsTableEngine extends Migration
{
    public function up()
    {
        $tables = ['products', 'product_attributes', 'product_attribute_values'];

        foreach ($tables as $table) {
            $this->db->query("ALTER TABLE `{$table}` ENGINE = InnoDB");
        }
    }

    public function down()
    {
        $tables = ['products', 'product_attributes', 'product_attribute_values'];

        foreach ($tables as $table) {
            $this->db->query("ALTER TABLE `{$table}` ENGINE = MyISAM");
        }
    }
}
