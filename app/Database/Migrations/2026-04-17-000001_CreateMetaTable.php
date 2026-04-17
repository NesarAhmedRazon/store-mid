<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Meta table — generic key-value store for any entity in the system.
 *
 * Design follows the existing media_entities pattern:
 *   entity_type + entity_id  →  identifies the owner (product, order, user, page, post)
 *   slug                     →  machine-readable key  e.g. "sku", "weight", "custom_note"
 *   label                    →  human-readable title  e.g. "SKU", "Weight (kg)"
 *   value                    →  the stored value      e.g. "AS458"
 *
 * Example rows:
 *   entity_type='product', entity_id=12, slug='sku',    label='SKU',    value='AS458'
 *   entity_type='product', entity_id=12, slug='weight', label='Weight', value='0.5'
 *   entity_type='order',   entity_id=99, slug='note',   label='Note',   value='Fragile'
 *   entity_type='user',    entity_id=3,  slug='phone',  label='Phone',  value='01700000000'
 */
class CreateMetaTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],

            // Which entity owns this meta — mirrors media_entities.entity_type
            'entity_type' => [
                'type'       => 'ENUM',
                'constraint' => ['product', 'order', 'user', 'page', 'post'],
                'comment'    => 'Owner entity type',
            ],

            // The PK of the owning row in its own table
            'entity_id' => [
                'type'    => 'INT',
                'comment' => 'FK to the owning entity\'s id',
            ],

            // Machine-readable key — used in code, URLs, lookups
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'comment'    => 'Machine key e.g. sku, weight, custom_note',
            ],

            // Human-readable label — displayed in admin UI
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
                'comment'    => 'Display label e.g. SKU, Weight (kg)',
            ],

            // Stored as TEXT to support any value length / type
            // Cast to the right type in application code as needed
            'value' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
            ],

            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);

        // Primary lookup: all metas for one entity
        $this->forge->addKey(['entity_type', 'entity_id']);

        // Unique constraint: one slug per entity
        // Prevents duplicate meta keys on the same entity without application-level checks
        $this->forge->addUniqueKey(['entity_type', 'entity_id', 'slug']);

        // Slug-only index for cross-entity queries: "find all entities with meta slug=sku"
        $this->forge->addKey('slug');

        $this->forge->createTable('meta', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('meta', true);
    }
}
