<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMediaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],

            // 'url'        = external URL only (e.g. WooCommerce image, no download)
            // 'local'      = uploaded to public/media/
            // 'cloudinary' = TODO: future CDN delivery
            'disk' => [
                'type'       => 'ENUM',
                'constraint' => ['url', 'local', 'cloudinary'],
                'default'    => 'local',
            ],

            // disk=url   → full external URL  (https://example.com/wp-content/image.jpg)
            // disk=local → relative from public/  (media/2026/03/image.jpg)
            'path' => [
                'type'       => 'VARCHAR',
                'constraint' => 1000,
            ],

            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'comment'    => 'Original filename; null for external URLs',
            ],

            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'comment'    => 'e.g. image/jpeg, application/pdf',
            ],

            // Bytes — null for external URLs we did not download
            'size' => [
                'type'    => 'INT',
                'null'    => true,
                'default' => null,
            ],

            // Image dimensions — null for non-image types
            'width' => [
                'type'    => 'SMALLINT',
                'null'    => true,
                'default' => null,
            ],
            'height' => [
                'type'    => 'SMALLINT',
                'null'    => true,
                'default' => null,
            ],

            'alt' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],

            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],

            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('disk');
        $this->forge->addKey('mime_type');

        $this->forge->createTable('media', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('media', true);
    }
}
