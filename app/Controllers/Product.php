<?php
// app/Controllers/Product.php
// This controller handles incoming POST requests from WooCommerce to create or update product records in the local database. It expects a JSON payload with product details and saves them using the ProductModel.

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProductModel;

class Product extends ResourceController
{
    public function receive()
        {
            log_message('debug', '=== Webhook Received ===');
            $secret = $this->request->getHeaderLine('X-WC-Webhook-Secret');
            log_message('debug', 'Received secret: ' . $secret);
            log_message('debug', 'Expected secret: ' . env('WC_WEBHOOK_SECRET'));

            if ($secret !== env('WC_WEBHOOK_SECRET')) {
                log_message('error', 'Invalid webhook secret provided');
                return $this->failUnauthorized('Invalid webhook secret');
            }

            $data = $this->request->getJSON(true);
            log_message('debug', 'Raw input: ' . json_encode($data));

            if (!$data || empty($data['wc_id']) || empty($data['title']) || empty($data['permalink'])) {
                log_message('error', 'Invalid or empty JSON payload');
                return $this->fail('Missing required fields', 422);
            }

            $model = new ProductModel();

            $insert = [
                'wc_id'          => $data['wc_id'],
                'permalink'      => $data['permalink'],
                'title'          => $data['title'],
                'sku'            => $data['sku'] ?? null,
                'stock_quantity' => $data['stock_quantity'] ?? null,
                'stock_status'   => $data['stock_status'] ?? 'outofstock',
                'sale_price'     => $data['sale_price'] ?? null,
                'regular_price'  => $data['regular_price'] ?? 0,
            ];
            log_message('debug', 'Prepared data: ' . json_encode($insert));
            
            $existing = $model->where('wc_id', $data['wc_id'])->first();

            if ($existing) {
                $model->update($existing->id, $insert);
            } else {
                $model->insert($insert);
            }

            return $this->respond([
                'status' => 'ok',
                'wc_id'  => $data['wc_id'],
            ]);
        }
}