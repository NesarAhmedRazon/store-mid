<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMediaEntitiesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],

            'media_id' => [
                'type' => 'INT',
                'null' => false,
            ],

            // Polymorphic owner: product | post | page | order
            'entity_type' => [
                'type'       => 'ENUM',
                'constraint' => ['product', 'post', 'page', 'order'],
                'null'       => false,
            ],

            'entity_id' => [
                'type' => 'INT',
                'null' => false,
            ],

            // thumbnail = featured image
            // gallery   = additional product/post images
            // attachment = downloadable file (PDF, Word, Excel)
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['thumbnail', 'gallery', 'attachment'],
                'default'    => 'gallery',
            ],

            // Display order within the same entity + role group
            'sort_order' => [
                'type'    => 'SMALLINT',
                'default' => 0,
            ],

            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);

        // Prevent duplicate assignments of the same media to the same entity+role
        $this->forge->addUniqueKey(['media_id', 'entity_type', 'entity_id', 'role']);

        // Fast lookups by owner
        $this->forge->addKey(['entity_type', 'entity_id']);

        $this->forge->addForeignKey(
            'media_id',
            'media',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('media_entities', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('media_entities', true);
    }
}
