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
        'stock_status',
        'sale_price',
        'regular_price',
        'wc_created_at',
        'thumb_id',
        'cost',
    ];

    protected $useTimestamps = true;

    /**
     * Get single product with attributes
     */
    public function getWithAttributes(int $productId)
    {
        $product = $this->find($productId);

        if (!$product) {
            return null;
        }

        $product->attributes = $this->getAttributes($productId);

        return $product;
    }

    /**
     * Get multiple products with attributes
     */
    public function getListWithAttributes(int $limit = 50, int $offset = 0)
    {
        $products = $this->findAll($limit, $offset);

        if (!$products) {
            return [];
        }

        $productIds = array_column($products, 'id');

        $attributesMap = $this->getAttributesBulk($productIds);

        foreach ($products as &$product) {
            $product['attributes'] = $attributesMap[$product['id']] ?? [];
        }

        return $products;
    }

    /**
     * Get attributes for single product
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
     * Bulk attributes (VERY IMPORTANT for performance)
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
            $pid = $row['product_id'];
            $attr = $row['attribute'];
            $value = $row['value'];

            $result[$pid][$attr][] = $value;
        }

        return $result;
    }

    /**
     * Format attribute rows into grouped structure
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