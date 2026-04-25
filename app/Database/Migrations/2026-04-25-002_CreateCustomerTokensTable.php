<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerTokensTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'customer_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null'     => false,
            ],
            // Stored as SHA-256 hash of the plain token sent to the client.
            'token_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'comment'    => 'e.g. "google", "facebook", "manual"',
            ],
            'last_used_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'expires_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'comment' => 'NULL = never expires (admin-issued tokens)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey('customer_id');
        $this->forge->addKey('expires_at');

        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('customer_tokens', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('customer_tokens', true);
    }
}
