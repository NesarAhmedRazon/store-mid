<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table      = 'products';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'wc_id',
        'permalink',
        'title',
        'sku',
        'stock_quantity',
        'stock_unit',
        'stock_status',
        'sale_price',
        'regular_price',
        'wc_created_at',
        'thumb_id',  // FK → media.id (thumbnail shortcut, no join needed)
        'cost',
        // gallery lives in media_entities — no gallery_ids column
    ];

    protected $useTimestamps = true;

    // ── Single product ───────────────────────────────────────────────────

    /**
     * Get a single product with its attributes and media.
     */
    public function getWithDetails(int $productId): ?object
    {
        $product = $this->find($productId);

        if (!$product) {
            return null;
        }

        $mediaModel = new MediaModel();

        $product->attributes = $this->getAttributes($productId);
        $product->media      = $mediaModel->getForEntity('product', $productId);

        return $product;
    }

    // ── Product list ─────────────────────────────────────────────────────

    /**
     * Get multiple products with attributes and media.
     * Uses bulk queries — one extra query for attributes, one for media.
     */
    public function getListWithDetails(int $limit = 50, int $offset = 0): array
    {
        $products = $this->findAll($limit, $offset);

        if (!$products) {
            return [];
        }

        $productIds = array_column($products, 'id');

        $attributesMap = $this->getAttributesBulk($productIds);

        $mediaModel = new MediaModel();
        $mediaMap   = $mediaModel->getForEntities('product', $productIds);

        foreach ($products as $product) {
            $product->attributes = $attributesMap[$product->id] ?? [];
            $product->media      = $mediaMap[$product->id]      ?? [
                'thumbnail'  => null,
                'gallery'    => [],
                'attachment' => [],
            ];
        }

        return $products;
    }

    // ── Attributes ───────────────────────────────────────────────────────

    /**
     * Get attributes for a single product.
     */
    private function getAttributes(int $productId): array
    {
        $rows = $this->db->table('product_attribute_map pam')
            ->select('pa.name as attribute, pav.name as value')
            ->join('product_attributes pa', 'pa.id = pam.attribute_id')
            ->join('product_attribute_values pav', 'pav.id = pam.value_id')
            ->where('pam.product_id', $productId)
            ->get()
            ->getResultArray();

        return $this->formatAttributes($rows);
    }

    /**
     * Bulk fetch attributes for multiple products (one query).
     */
    private function getAttributesBulk(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $rows = $this->db->table('product_attribute_map pam')
            ->select('pam.product_id, pa.name as attribute, pav.name as value')
            ->join('product_attributes pa', 'pa.id = pam.attribute_id')
            ->join('product_attribute_values pav', 'pav.id = pam.value_id')
            ->whereIn('pam.product_id', $productIds)
            ->get()
            ->getResultArray();

        $result = [];

        foreach ($rows as $row) {
            $result[$row['product_id']][$row['attribute']][] = $row['value'];
        }

        return $result;
    }

    /**
     * Format flat attribute rows into a grouped structure.
     *
     * [ 'Color' => ['Red', 'Blue'], 'Size' => ['M', 'L'] ]
     */
    private function formatAttributes(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $result[$row['attribute']][] = $row['value'];
        }

        return $result;
    }
}