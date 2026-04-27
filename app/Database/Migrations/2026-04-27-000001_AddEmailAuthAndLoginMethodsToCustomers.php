<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailAuthAndLoginMethodsToCustomers extends Migration
{
    public function up(): void
    {
        // password — nullable, NULL means no email login set up
        $this->forge->addColumn('customers', [
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'facebook_id',
                'comment'    => 'Bcrypt hash. NULL = no email login.',
            ],
        ]);

        // login_methods — JSON array e.g. ["email","google","facebook"]
        $this->forge->addColumn('customers', [
            'login_methods' => [
                'type'    => 'JSON',
                'null'    => true,
                'default' => null,
                'after'   => 'password',
                'comment' => 'Which login methods this customer has used.',
            ],
        ]);

        // last_active_at — updated on every authenticated request,
        // used solely for the 90-day inactivity check. Does NOT affect login.
        $this->forge->addColumn('customers', [
            'last_active_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'last_login_at',
                'comment' => 'Updated on every API request. Used for 90-day inactivity marking.',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('customers', 'password');
        $this->forge->dropColumn('customers', 'login_methods');
        $this->forge->dropColumn('customers', 'last_active_at');
    }
}
