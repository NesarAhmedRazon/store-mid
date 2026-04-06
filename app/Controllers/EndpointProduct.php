<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CategoryModel;

class EndpointProduct extends ResourceController
{
    protected $format = 'json';

    /**
     * GET /products
     * GET /products/{category_slug}
     */
    public function send(string $categorySlug = null): \CodeIgniter\HTTP\ResponseInterface
    {
        // ── Auth ────────────────────────────────────────────────────────
        $secret = $this->request->getHeaderLine('x-front-webhook-secret');

        if ($secret !== env('FRONT_WEBHOOK_SECRET')) {
            log_message('error', 'Invalid webhook secret provided');
            return $this->failUnauthorized('Invalid webhook secret');
        }


        $db = \Config\Database::connect();

        // ── Base query ───────────────────────────────────────────────────
        $builder = $db->table('products p')
                      ->select('p.title, p.permalink, p.updated_at');

        // ── Filter by category (+ descendants) if slug provided ──────────
        if ($categorySlug !== null) {

            $categoryModel = new CategoryModel();
            $category      = $categoryModel->where('slug', $categorySlug)->first();

            if (!$category) {
                return $this->respond([
                    'status'  => 'error',
                    'message' => 'Category not found',
                ], 404);
            }

            $productIds = $categoryModel->getProductIds($category->id, includeDescendants: true);

            if (empty($productIds)) {
                return $this->respond([
                    'status'    => 'ok',
                    'category'  => $category->name,
                    'total'     => 0,
                    'products'  => [],
                ]);
            }

            $builder->whereIn('p.id', $productIds);
        }

        // ── Execute ───────────────────────────────────────────────────────
        $products = $builder
            ->orderBy('p.updated_at', 'DESC')
            ->get()
            ->getResultArray();
        // ── Process permalinks ────────────────────────────────────────────
        foreach ($products as &$product) {
            if (isset($product['permalink'])) {
                // Remove /product/, /products/, product/, products/ from the beginning
                $product['permalink'] = '/' . ltrim(preg_replace('/^\/?(product|products)\/?/', '', $product['permalink']), '/');
                // Remove trailing slash from the end
                $product['permalink'] = rtrim($product['permalink'], '/');
            }
        }
        // ── Response ──────────────────────────────────────────────────────
        $payload = [
            'status'   => 'ok',
            'total'    => count($products),
            'products' => $products,
        ];

        if ($categorySlug !== null) {
            $payload['category'] = $category->name;
        }

        return $this->respond($payload);
    }
}