<?php
// app/Controllers/Product.php
// Handles incoming POST requests from WooCommerce.
// Upserts product, attributes, values, and pivot map in a single transaction.

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProductModel;
use CodeIgniter\I18n\Time;

class Product extends ResourceController
{
    public function receive()
    {
        // ── Auth ────────────────────────────────────────────────────────
        $secret = $this->request->getHeaderLine('X-WC-Webhook-Secret');

        if ($secret !== env('WC_WEBHOOK_SECRET')) {
            log_message('error', 'Invalid webhook secret provided');
            return $this->failUnauthorized('Invalid webhook secret');
        }

        // ── Parse payload ───────────────────────────────────────────────
        $data = $this->request->getJSON(true);

        if (!$data || empty($data['wc_id']) || empty($data['title']) || empty($data['permalink'])) {
            log_message('error', 'Invalid or empty JSON payload');
            return $this->fail('Missing required fields', 422);
        }

        $db    = \Config\Database::connect();
        $model = new ProductModel();

        try {
            $db->transStart();

            // ── Upsert product ───────────────────────────────────────────
            $productData = [
                'wc_id'          => $data['wc_id'],
                'permalink'      => $data['permalink'],
                'title'          => $data['title'],
                'sku'            => $data['sku'] ?? null,
                'stock_quantity' => $data['stock_quantity'] ?? null,
                'stock_status'   => $data['stock_status'] ?? 'outofstock',
                'sale_price'     => $data['sale_price'] ?? null,
                'regular_price'  => $data['regular_price'] ?? 0,
                'wc_created_at'  => $data['created_at'] ?? Time::now()->toDateTimeString(),
                'cost'           => $data['wc_cog'] ?? 0,
            ];

            $existing = $model->where('wc_id', $data['wc_id'])->first();

            if ($existing) {
                $model->update($existing->id, $productData);
                $productId = $existing->id;
            } else {
                $productId = $model->insert($productData, true);
            }

            // ── Upsert attributes ────────────────────────────────────────
            if (!empty($data['attributes']) && is_array($data['attributes'])) {

                // Clear existing pivot rows — will be re-linked fresh below
                $db->table('product_attribute_map')
                   ->where('product_id', $productId)
                   ->delete();

                foreach ($data['attributes'] as $attr) {

                    if (empty($attr['wc_id']) || empty($attr['name']) || empty($attr['values'])) {
                        continue;
                    }

                    // ── Upsert attribute row ─────────────────────────────
                    $attrRow = $db->table('product_attributes')
                                  ->where('wc_id', (int) $attr['wc_id'])
                                  ->get()->getRowArray();

                    if ($attrRow) {
                        $db->table('product_attributes')
                           ->where('wc_id', (int) $attr['wc_id'])
                           ->update([
                               'label'     => $attr['label']     ?? $attrRow['label'],
                               'is_public' => (int) ($attr['is_public'] ?? $attrRow['is_public']),
                           ]);
                        $attributeId = $attrRow['id'];
                    } else {
                        $db->table('product_attributes')->insert([
                            'wc_id'     => (int) $attr['wc_id'],
                            'name'      => $attr['name'],
                            'label'     => $attr['label'] ?? $attr['name'],
                            'is_public' => (int) ($attr['is_public'] ?? 0),
                        ]);
                        $attributeId = $db->insertID();
                    }

                    // ── Upsert each value + link to product ──────────────
                    foreach ($attr['values'] as $val) {

                        if (empty($val['wc_id']) || empty($val['name'])) {
                            continue;
                        }

                        $valRow = $db->table('product_attribute_values')
                                     ->where('wc_id', (int) $val['wc_id'])
                                     ->get()->getRowArray();

                        if ($valRow) {
                            $db->table('product_attribute_values')
                               ->where('wc_id', (int) $val['wc_id'])
                               ->update([
                                   'name' => $val['name'],
                                   'slug' => $val['slug'] ?? $valRow['slug'],
                               ]);
                            $valueId = $valRow['id'];
                        } else {
                            $db->table('product_attribute_values')->insert([
                                'wc_id'        => (int) $val['wc_id'],
                                'attribute_id' => $attributeId,
                                'slug'         => $val['slug'] ?? '',
                                'name'         => $val['name'],
                            ]);
                            $valueId = $db->insertID();
                        }

                        // ── Insert pivot row ─────────────────────────────
                        $db->table('product_attribute_map')->insert([
                            'product_id'   => $productId,
                            'attribute_id' => $attributeId,
                            'value_id'     => $valueId,
                            'order'        => $attr['order'] ?? null,
                        ]);
                    }
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                log_message('error', 'Transaction failed for wc_id: ' . $data['wc_id']);
                return $this->fail('Transaction failed', 500);
            }

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Product receive exception: ' . $e->getMessage());
            return $this->fail('Server error: ' . $e->getMessage(), 500);
        }

        return $this->respond([
            'status'     => 'ok',
            'wc_id'      => $data['wc_id'],
            'product_id' => $productId,
        ]);
    }
}