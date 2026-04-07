<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CategoryModel;
use App\Models\MediaModel;
use App\Libraries\ProductSorter;
use App\Models\ProductModel;

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
    // 1. Auth
    $secret = $this->request->getHeaderLine('x-front-webhook-secret');
    $check_secret = env('FRONT_WEBHOOK_SECRET');
    if (!hash_equals(trim($check_secret), trim($secret))) {
        
        return $this->failUnauthorized('Invalid webhook secret');
    }

    // 2. Inputs
    $view = $this->request->getGet('view') ?? 'full';
    $perPage = $this->request->getGet('per_page') ?? 10;
    $page = $this->request->getGet('page') ?? 1;

    $mode = in_array($view, ['minimal', 'summary', 'full']) ? $view : 'full';

    $db = \Config\Database::connect();

    // 3. Select Fields (CRITICAL: Always include 'id' for processing)
    if ($mode === 'minimal') {
        $select = 'p.id, p.title, p.permalink, p.updated_at';
    } elseif ($mode === 'summary') {
        $select = 'p.id, p.title, p.permalink, p.updated_at, p.stock_status, p.regular_price';
    } else {
        $select = 'p.*'; 
    }

    // impliment pagination 
    
    if($perPage == 'all' && $mode === 'minimal') {
        $builder = $db->table('products p')->select($select);
    } else {
       $builder = $db->table('products p')->select($select)->limit($perPage, ($page - 1) * $perPage);
    }

    

    // 4. Category Filter Logic (Your existing logic)
    if ($categorySlug !== null) {
        $categoryModel = new CategoryModel();
        $category = $categoryModel->where('slug', $categorySlug)->first();
        if (!$category) return $this->respond(['status' => 'error', 'message' => 'Category not found'], 404);

        $productIds = $categoryModel->getProductIds($category->id, true);
        if (empty($productIds)) {
            return $this->respond(['status' => 'ok', 'total' => 0, 'products' => []]);
        }
        $builder->whereIn('p.id', $productIds);
    }

    // 5. Execute DB Query
    $products = $builder->orderBy('p.updated_at', 'DESC')->get()->getResultArray();

    // 6. Bulk Fetch Media (Only if NOT minimal)
    $mediaMap = [];
    $mediaModel = new MediaModel();
    if ($mode !== 'minimal' && !empty($products)) {
        $targetIds = array_column($products, 'id');
        $roles = ($mode === 'summary') ? ['thumbnail'] : ['thumbnail', 'gallery'];
        $mediaMap = $mediaModel->getForEntities('product', $targetIds, $roles);
    }

    // 7. Sort (Only if NOT minimal)
    if ($mode !== 'minimal') {
        $products = \App\Libraries\ProductSorter::sort($products);
    }

    // 8. Final Transformation Loop
    $finalProducts = [];
    foreach ($products as $product) {
        // Clean Permalink
        if (!empty($product['permalink'])) {
            // 1. Remove the 'product/' or 'products/' prefix
            $cleanPath = preg_replace('/^\/?(product|products)\/?/', '', $product['permalink']);
            
            // 2. Trim slashes from BOTH ends without prepending a new one
            $product['permalink'] = trim($cleanPath, '/');
        }
        

        // Attach Images (using the model helper)
        if ($mode !== 'minimal') {
            
            // Pass $this (the controller) if formatImage is in the controller, 
        
            // IF mode is summary, we only want the thumbnail, so we can tell formatImages to ignore gallery.
            if($mode === 'summary') {
                $media = [
                    'thumbnail' => $mediaMap[$product['id']]['thumbnail'] ?? null,
                    'gallery' => [],
                ];
            }else{
                $media = $mediaMap[$product['id']] ?? ['thumbnail' => null, 'gallery' => []];
            }

        
            // OR move formatImage to the Model too.
            $product['images'] = $mediaModel->getFlatImages($media, $mode);
        }

        // 9. CLEANUP
        foreach (array_keys($product) as $key) {
            if (preg_match('/^wc_/', $key)) {
                unset($product[$key]);
            }
        }
        unset($product['id']);
        if(isset($product['thumb_id'])) unset($product['thumb_id']);

        $finalProducts[] = $product;
    }

    return $this->respond([
        'status'   => 'ok',
        'view'     => $mode,
        'total'    => count($finalProducts),
        'products' => $finalProducts,
    ]);
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


}