<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCourierTables extends Migration
{

    public function up()
    {

        /*
        |--------------------------------------------------------------------------
        | courier_providers
        |--------------------------------------------------------------------------
        */

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true
            ],
            'webhook_secret' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'auth_token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('courier_providers', true);


        /*
        |--------------------------------------------------------------------------
        | shipments
        |--------------------------------------------------------------------------
        */

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'auto_increment' => true
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'consignment_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100
            ],
            'merchant_order_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ],
            'invoice' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ],
            'current_status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true
            ],
            'cod_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true
            ],
            'delivery_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true
            ],
            'created_at DATETIME NULL',
            'updated_at DATETIME NULL'
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['provider', 'consignment_id']);
        $this->forge->createTable('shipments', true);


        /*
        |--------------------------------------------------------------------------
        | shipment_events
        |--------------------------------------------------------------------------
        */

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'auto_increment' => true
            ],
            'shipment_id' => [
                'type' => 'BIGINT'
            ],
            'provider_event' => [
                'type' => 'VARCHAR',
                'constraint' => 100
            ],
            'normalized_status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'event_time DATETIME NULL',
            'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('shipment_id');
        $this->forge->createTable('shipment_events', true);


        /*
        |--------------------------------------------------------------------------
        | courier_webhooks
        |--------------------------------------------------------------------------
        */

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'auto_increment' => true
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'consignment_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ],
            'merchant_reference' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ],
            'payload' => [
                'type' => 'JSON'
            ],
            'headers' => [
                'type' => 'JSON',
                'null' => true
            ],
            'received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('courier_webhooks', true);
    }


    public function down()
    {
        $this->forge->dropTable('courier_webhooks', true);
        $this->forge->dropTable('shipment_events', true);
        $this->forge->dropTable('shipments', true);
        $this->forge->dropTable('courier_providers', true);
    }
}
