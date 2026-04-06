<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CategoryModel;
use App\Models\MediaModel;
use App\Libraries\ProductSorter;

class EndpointProduct extends ResourceController
{
    protected $format = 'json';

    /**
     * GET /api/get/products
     * GET /api/get/products/{categorySlug}
     *
     * Query params:
     *   mode=slug_only  → title + permalink only, no sorting
     *   mode=full       → all fields + sorted (default behaviour)
     *   page=N          → placeholder for future pagination
     *   per_page=N      → placeholder for future pagination
     */
    public function send(string $categorySlug = null): \CodeIgniter\HTTP\ResponseInterface
    {
        // ── Auth ─────────────────────────────────────────────────────────
        $secret = $this->request->getHeaderLine('x-front-webhook-secret');

        if ($secret !== env('FRONT_WEBHOOK_SECRET')) {
            log_message('error', 'Invalid webhook secret provided');
            return $this->failUnauthorized('Invalid webhook secret');
        }

        $mode     = $this->request->getGet('mode') ?? 'full';
        $slugOnly = $mode === 'slug_only';

        $db = \Config\Database::connect();

        // ── Select fields ─────────────────────────────────────────────────
        // slug_only: minimal fields — no sorting needed
        // full:      include stock_status + regular_price for ProductSorter
        $select = $slugOnly
            ? 'p.id, p.title, p.permalink, p.updated_at'
            : 'p.id, p.title, p.permalink, p.updated_at, p.stock_status, p.regular_price';

        $builder = $db->table('products p')->select($select);

        // ── Filter by category (+ descendants) if slug provided ───────────
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
                    'status'   => 'ok',
                    'category' => $category->name,
                    'total'    => 0,
                    'products' => [],
                ]);
            }

            $builder->whereIn('p.id', $productIds);
        }

        // ── Execute ───────────────────────────────────────────────────────
        $products = $builder
            ->orderBy('p.updated_at', 'DESC')
            ->get()
            ->getResultArray();

        // ── Sort + filter (skip for slug_only) ────────────────────────────
        if (!$slugOnly) {
            $products = ProductSorter::sort($products);
        }

        // ── Clean permalinks ──────────────────────────────────────────────
        foreach ($products as &$product) {
            if (!empty($product['permalink'])) {
                $product['permalink'] = '/' . ltrim(
                    preg_replace('/^\/?(product|products)\/?/', '', $product['permalink']),
                    '/'
                );
                $product['permalink'] = rtrim($product['permalink'], '/');
            }
        }
        unset($product);

        // ── Strip sorter-only fields before responding ────────────────────
        // Don't expose stock_status / regular_price unless the caller needs them
        if (!$slugOnly) {
            $products = array_map(function ($p) {
                unset($p['stock_status'], $p['regular_price']);
                return $p;
            }, $products);
        }

        // ── Attach images (skip for slug_only) ────────────────────────────
        if (!$slugOnly) {
            $productIds  = array_column($products, 'id');
            $mediaModel  = new MediaModel();
            $mediaMap    = $mediaModel->getForEntities('product', $productIds);

            $products = array_map(function ($p) use ($mediaMap, $mediaModel) {
                $media     = $mediaMap[$p['id']] ?? ['thumbnail' => null, 'gallery' => []];
                $p['images'] = $this->formatImages($media, $mediaModel);
                unset($p['id']); // id was only needed for the media join
                return $p;
            }, $products);
        } else {
            // slug_only — just drop the id
            $products = array_map(function ($p) {
                unset($p['id']);
                return $p;
            }, $products);
        }

        // ── Response ──────────────────────────────────────────────────────
        $payload = [
            'status'      => 'ok',
            'mode'        => $mode,
            'total'       => count($products),
            'total_pages' => 1, // TODO: implement real pagination
            'products'    => $products,
        ];

        if ($categorySlug !== null) {
            $payload['category'] = $category->name;
        }

        return $this->respond($payload);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Format a media group (from MediaModel::getForEntities) into the
     * ProductImages shape the frontend expects.
     *
     * {
     *   thumbnail: ProductImage | null,
     *   gallery:   ProductImage[]
     * }
     */
    private function formatImages(array $media, MediaModel $mediaModel): array
    {
        return [
            'thumbnail' => $media['thumbnail']
                ? $this->formatImage($media['thumbnail'], $mediaModel)
                : null,
            'gallery' => array_map(
                fn($img) => $this->formatImage($img, $mediaModel),
                $media['gallery'] ?? []
            ),
        ];
    }

    private function formatImage(object $media, MediaModel $mediaModel): array
    {
        return [
            'src'       => $mediaModel->resolveUrl($media),
            'alt'       => $media->alt   ?? null,
            'title'     => $media->title ?? null,
            'width'     => $media->width  ? (int) $media->width  : null,
            'height'    => $media->height ? (int) $media->height : null,
            'sort_order'=> (int) $media->sort_order,
        ];
    }
}