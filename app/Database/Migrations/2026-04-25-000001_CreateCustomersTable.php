<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            // ── External identifiers ─────────────────────────────────────
            'wp_user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'comment'  => 'WooCommerce user ID',
            ],
            'google_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => true,
                'default'    => null,
            ],
            'facebook_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => true,
                'default'    => null,
            ],

            // ── Core fields ──────────────────────────────────────────────
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'default'    => null,
            ],
            'avatar_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 512,
                'null'       => true,
                'default'    => null,
            ],
            'billing_address' => [
                'type'    => 'JSON',
                'null'    => true,
                'default' => null,
                'comment' => 'JSON: line1, line2, city, state, postcode, country',
            ],

            // ── Status & source ──────────────────────────────────────────
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive', 'banned'],
                'default'    => 'active',
            ],
            'source' => [
                'type'       => 'ENUM',
                'constraint' => ['wp_import', 'google', 'facebook', 'manual'],
                'null'       => false,
                'comment'    => 'How this customer was first created',
            ],

            // ── Timestamps ───────────────────────────────────────────────
            'last_login_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('wp_user_id');
        $this->forge->addUniqueKey('google_id');
        $this->forge->addUniqueKey('facebook_id');
        $this->forge->addKey('status');
        $this->forge->addKey('source');

        $this->forge->createTable('customers', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('customers', true);
    }
}
