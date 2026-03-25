<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'unique' => true,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'role' => [
                'type' => 'ENUM',
                'constraint' => ['admin', 'staff', 'viewer'],
                'default' => 'viewer',
            ],
            'status' => [
                'type' => 'BOOLEAN',
                'default' => 1,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('users', true);

        // 🔽 Insert default admin (SAFE)
        $db = \Config\Database::connect();

        $email = env('ADMIN_EMAIL', 'admin@example.com');

        $existing = $db->table('users')
            ->where('email', $email)
            ->get()
            ->getRow();

        if (!$existing) {

            $db->table('users')->insert([
                'name' => env('ADMIN_NAME', 'Super Admin'),
                'email' => $email,
                'password' => password_hash(
                    env('ADMIN_PASSWORD', 'changeme123'),
                    PASSWORD_DEFAULT
                ),
                'role' => 'admin',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('users', true);
    }
}