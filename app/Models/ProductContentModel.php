<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ProductContentModel — manages the product_content table.
 *
 * One row per product. Always use upsert() — never insert/update directly.
 */
class ProductContentModel extends Model
{
    protected $table      = 'product_content';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'product_id',
        'html',
        'css',
    ];

    protected $useTimestamps = false; // updated_at is managed by DB DEFAULT

    // ── Write ─────────────────────────────────────────────────────────────

    /**
     * Insert or update content for a product.
     *
     * Uses INSERT … ON DUPLICATE KEY UPDATE to leverage the unique index
     * on product_id — one query, no race condition.
     *
     * @param int         $productId
     * @param string|null $html
     * @param string|null $css
     */
    public function upsert(int $productId, ?string $html, ?string $css): void
    {
        $this->db->query(
            'INSERT INTO product_content (product_id, html, css)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 html       = VALUES(html),
                 css        = VALUES(css),
                 updated_at = CURRENT_TIMESTAMP',
            [$productId, $html, $css]
        );
    }

    // ── Read ──────────────────────────────────────────────────────────────

    /**
     * Get content for a single product.
     * Returns [ 'html' => ..., 'css' => ... ] or null if not found.
     */
    public function getForProduct(int $productId): ?array
    {
        $row = $this->select('html, css')
                    ->where('product_id', $productId)
                    ->first();

        return $row ?: null;
    }

    /**
     * Bulk-fetch content for many products — one query.
     * Returns: [ product_id => [ 'html' => ..., 'css' => ... ] ]
     */
    public function getForProducts(array $productIds): array
    {
        if (empty($productIds)) return [];

        $rows = $this->select('product_id, html, css')
                     ->whereIn('product_id', $productIds)
                     ->findAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['product_id']] = [
                'html' => $row['html'],
                'css'  => $row['css'],
            ];
        }

        return $result;
    }
}
