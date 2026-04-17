<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MetaModel — CRUD and bulk helpers for the meta table.
 *
 * All public methods take ($entityType, $entityId) as the first two params
 * so callers never have to think about the underlying schema.
 *
 * Usage examples:
 *
 *   $meta = new MetaModel();
 *
 *   // Save or update one key
 *   $meta->put('product', 12, 'weight', 'Weight (kg)', '0.5');
 *
 *   // Bulk-save from a payload array
 *   $meta->syncFromPayload('product', 12, $data['meta'] ?? []);
 *
 *   // Read one key
 *   $weight = $meta->get('product', 12, 'weight');      // → '0.5'
 *
 *   // Read all metas as a flat [ slug => value ] map
 *   $map = $meta->getMap('product', 12);
 *
 *   // Read full rows (id, slug, label, value, …)
 *   $rows = $meta->getAll('product', 12);
 *
 *   // Bulk-read for many entities — one query, no N+1
 *   $map = $meta->getMapBulk('product', [12, 13, 14]);
 *   // → [ 12 => ['sku' => 'X', ...], 13 => [...], ... ]
 *
 *   // Delete one key
 *   $meta->remove('product', 12, 'weight');
 *
 *   // Delete all metas for an entity (e.g. before re-syncing)
 *   $meta->removeAll('product', 12);
 */
class MetaModel extends Model
{
    protected $table      = 'meta';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'entity_type',
        'entity_id',
        'slug',
        'label',
        'value',
    ];

    protected $useTimestamps = true;

    // ── Supported entity types (mirrors migration ENUM) ───────────────────
    public const ENTITY_PRODUCT = 'product';
    public const ENTITY_ORDER   = 'order';
    public const ENTITY_USER    = 'user';
    public const ENTITY_PAGE    = 'page';
    public const ENTITY_POST    = 'post';

    // ── Write ─────────────────────────────────────────────────────────────

    /**
     * Insert or update a single meta key for an entity.
     *
     * Uses INSERT … ON DUPLICATE KEY UPDATE to leverage the unique index
     * on (entity_type, entity_id, slug) — one query, no race condition.
     */
    public function put(
        string $entityType,
        int    $entityId,
        string $slug,
        ?string $label,
        ?string $value
    ): bool {
        $db = $this->db;

        $db->query(
            'INSERT INTO meta (entity_type, entity_id, slug, label, value)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 label      = VALUES(label),
                 value      = VALUES(value),
                 updated_at = CURRENT_TIMESTAMP',
            [$entityType, $entityId, $slug, $label, $value]
        );

        return $db->affectedRows() > 0;
    }

    /**
     * Bulk-sync metas from a payload array.
     *
     * Expects each item to have: slug (required), label (optional), value (optional).
     * Unrecognized or empty slugs are silently skipped.
     *
     * Pass $replace = true to wipe existing metas first (full replace).
     * Pass $replace = false (default) to upsert on top of existing metas.
     *
     * Example payload shape (mirrors WC webhook / API convention):
     *   [
     *     ['slug' => 'sku',    'label' => 'SKU',    'value' => 'AS458'],
     *     ['slug' => 'weight', 'label' => 'Weight', 'value' => '0.5'],
     *   ]
     */
    public function syncFromPayload(
        string $entityType,
        int    $entityId,
        array  $payload,
        bool   $replace = false
    ): void {
        
        
        if (empty($payload)) {
            return;
        }

        if ($replace) {
            $this->removeAll($entityType, $entityId);
        }

        foreach ($payload as $key => $value) {
            log_message('info', "Processing key: {$key}");
            
            // Handle different value types appropriately
            if (is_array($value)) {
                // For arrays/objects, store as JSON for flexibility
                $stringValue = json_encode($value);
                
                $this->put(
                    $entityType,
                    $entityId,
                    $key,
                    $key,
                    $stringValue
                );
            } else {
                // Simple scalar values stored directly
                $this->put(
                    $entityType,
                    $entityId,
                    $key,
                    $key,
                    (string) $value
                );
            }
        }
    }

    // ── Read ──────────────────────────────────────────────────────────────

    /**
     * Get the value of a single meta key, or $default if not found.
     */
    public function get(
        string  $entityType,
        int     $entityId,
        string  $slug,
        mixed   $default = null
    ): mixed {
        $row = $this->where('entity_type', $entityType)
                    ->where('entity_id',   $entityId)
                    ->where('slug',        $slug)
                    ->first();

        return $row ? $row['value'] : $default;
    }

    /**
     * Get all meta rows for an entity (full rows with id, label, etc.).
     *
     * @return array[]
     */
    public function getAll(string $entityType, int $entityId): array
    {
        return $this->where('entity_type', $entityType)
                    ->where('entity_id',   $entityId)
                    ->orderBy('slug', 'ASC')
                    ->findAll();
    }

    /**
     * Get a flat [ slug => value ] map for one entity.
     *
     * Useful when you only need values, not labels/timestamps.
     */
    public function getMap(string $entityType, int $entityId): array
    {
        $rows = $this->select('slug, value')
                     ->where('entity_type', $entityType)
                     ->where('entity_id',   $entityId)
                     ->findAll();

        return array_column($rows, 'value', 'slug');
    }

    /**
     * Bulk-read metas for many entities of the same type — ONE query.
     *
     * Returns: [ entity_id => [ slug => value ], … ]
     *
     * Example:
     *   $map = $meta->getMapBulk('product', [12, 13, 14]);
     *   $sku = $map[12]['sku'] ?? null;
     */
    public function getMapBulk(string $entityType, array $entityIds): array
    {
        if (empty($entityIds)) {
            return [];
        }

        $rows = $this->select('entity_id, slug, value')
                     ->where('entity_type', $entityType)
                     ->whereIn('entity_id', $entityIds)
                     ->findAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['entity_id']][$row['slug']] = $row['value'];
        }

        return $result;
    }

    // ── Delete ────────────────────────────────────────────────────────────

    /**
     * Delete one meta key for an entity.
     */
    public function remove(string $entityType, int $entityId, string $slug): bool
    {
        return $this->where('entity_type', $entityType)
                    ->where('entity_id',   $entityId)
                    ->where('slug',        $slug)
                    ->delete() > 0;
    }

    /**
     * Delete all metas for an entity.
     * Call before a full re-sync to avoid stale keys.
     */
    public function removeAll(string $entityType, int $entityId): bool
    {
        return $this->where('entity_type', $entityType)
                    ->where('entity_id',   $entityId)
                    ->delete() > 0;
    }
}
