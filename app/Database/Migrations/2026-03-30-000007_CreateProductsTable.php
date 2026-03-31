<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'gallery_ids' => [
                'type' => 'JSON',
                'null' => true,
                'default' => null,
            ],
        ]);

    }

    public function down()
    {
        $this->forge->dropTable('products', true);
    }
}