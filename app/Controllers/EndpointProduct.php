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
     *   view=minimal  → title + permalink only, no sorting
     *   view=summary → title + permalink + updated_at, sorted by updated_at desc
     *   view=full       → all fields + sorted (default behaviour)
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

        // ── Input Handling ───────────────────────────────────────────────
        $view    = $this->request->getGet('view') ?? 'full';
        $mode    = in_array($view, ['minimal', 'summary', 'full']) ? $view : 'full';
        
        // Pagination placeholders
        $page    = (int) ($this->request->getGet('page') ?? 1);
        $perPage = (int) ($this->request->getGet('per_page') ?? 100);

        $db = \Config\Database::connect();

        // ── Field Selection ──────────────────────────────────────────────
        // 'minimal': ID, Title, Permalink (No sorting fields needed)
        // 'summary': ID, Title, Permalink, Updated_at + Sorter fields
        // 'full':    * (Everything)
        if ($mode === 'minimal') {
            $select = 'p.title, p.permalink';
        } elseif ($mode === 'summary') {
            $select = 'p.id, p.title, p.permalink, p.updated_at, p.stock_status, p.regular_price';
        } else {
            $select = 'p.*'; // Or list all specific full fields
        }

        $builder = $db->table('products p')->select($select);

        // ── Category Filter ──────────────────────────────────────────────
        if ($categorySlug !== null) {
            $categoryModel = new CategoryModel();
            $category      = $categoryModel->where('slug', $categorySlug)->first();

            if (!$category) {
                return $this->respond(['status' => 'error', 'message' => 'Category not found'], 404);
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

        // ── Execution ────────────────────────────────────────────────────
        // Default DB sort is updated_at desc
        $products = $builder->orderBy('p.updated_at', 'DESC')->get()->getResultArray();

        // ── Sorting (Skip for minimal) ───────────────────────────────────
        if ($mode !== 'minimal') {
            $products = ProductSorter::sort($products);
        }

        // ── Data Transformation ──────────────────────────────────────────
        $productIds = array_column($products, 'id');
        $mediaMap   = [];
        $mediaModel = new MediaModel();

        // Only fetch images if NOT minimal
        if ($mode !== 'minimal' && !empty($productIds)) {
            $mediaMap = $mediaModel->getForEntities('product', $productIds);
        }

        $finalProducts = [];
        foreach ($products as $product) {
            // 1. Clean Permalink
            if (!empty($product['permalink'])) {
                $product['permalink'] = '/' . ltrim(
                    preg_replace('/^\/?(product|products)\/?/', '', $product['permalink']),
                    '/'
                );
                $product['permalink'] = rtrim($product['permalink'], '/');
            }

            // 2. Attach Images (Summary & Full only)
            // ── Attach images (Only if NOT minimal) ────────────────────────────
            if ($mode !== 'minimal') {
                $productIds = array_column($products, 'id');
                $mediaModel = new MediaModel();

                // Decide which roles to fetch from the DB
                $requestedRoles = ($mode === 'summary') ? ['thumbnail'] : ['thumbnail', 'gallery'];
                
                $mediaMap = $mediaModel->getForEntities('product', $productIds, $requestedRoles);

                $products = array_map(function ($p) use ($mediaMap, $mediaModel, $mode) {
                    $media = $mediaMap[$p['id']] ?? ['thumbnail' => null, 'gallery' => []];
                    
                    if ($mode === 'summary') {
                        // Summary: Thumbnail only
                        $p['images'] = [
                            'thumbnail' => $media['thumbnail'] 
                                ? $this->formatImage($media['thumbnail'], $mediaModel) 
                                : null
                        ];
                    } else {
                        // Full: Thumbnail + Gallery
                        $p['images'] = $this->formatImages($media, $mediaModel);
                    }

                    unset($p['id']); 
                    return $p;
                }, $products);
            } else {
                // Minimal: Just drop IDs
                $products = array_map(function ($p) {
                    unset($p['id']);
                    return $p;
                }, $products);
            }

            // 3. Strip Internal/Unnecessary Fields
            // Remove fields used only for sorting
            unset($product['stock_status'], $product['regular_price']);
            
            // Remove ID if not explicitly needed in payload (usually minimal wants it as 'id')
            // If you want to keep ID for all, remove this line:
            if ($mode !== 'minimal') unset($product['id']); 

            $finalProducts[] = $product;
        }

        // ── Response ──────────────────────────────────────────────────────
        $payload = [
            'status'      => 'ok',
            'view'        => $mode,
            'total'       => count($finalProducts),
            'total_pages' => 1, 
            'products'    => $finalProducts,
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