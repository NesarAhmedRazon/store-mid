<?php

namespace App\Libraries;

class AttributeService
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /**
     * Get attributes by product ID
     */
    public function getByProductId(int $productId): array
{
    $rows = $this->db->table('product_attribute_map pam')
        ->select('pa.id as attribute_id, pa.label as name, pav.name as value, pam.order')
        ->join('product_attributes pa', 'pa.id = pam.attribute_id')
        ->join('product_attribute_values pav', 'pav.id = pam.value_id')
        ->where('pam.product_id', $productId)
        ->orderBy('pam.order', 'ASC')
        ->get()
        ->getResultArray();

    $merged = [];
    foreach ($rows as $row) {
        $attributeId = $row['attribute_id'];
        $order = $row['order'] ?? null;
        
        if (!isset($merged[$attributeId])) {
            $merged[$attributeId] = [
                'name'   => $row['name'],
                'values' => [],
                'order'  => $order
            ];
        }
        
        $merged[$attributeId]['values'][] = $row['value'];
    }

    // Convert to desired format (values as comma-separated string)
    $result = [];
    foreach ($merged as $item) {
        $result[] = [
            'name'  => $item['name'],
            'value' => implode(', ', $item['values']),
            'order'  => $item['order']
        ];
    }
    usort($result, function($a, $b) {
        return $a['order'] <=> $b['order'];
    });
    return $result;
}
}