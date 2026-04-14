<?php

namespace App\Libraries;

use App\Models\CategoryModel;
use App\Models\MediaModel;
use App\Libraries\ProductSorter;

class ProductFetcher
{
    protected $db;
    protected $mediaModel;
    protected $categoryModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->mediaModel = new MediaModel();
        $this->categoryModel = new CategoryModel();
    }

    /**
     * Centralized logic to fetch and format products
     */
    public function getProducts(array $options = []): array
    {
        $mode         = $options['mode'] ?? 'full';
        $perPage      = $options['perPage'] ?? 20;
        $page         = $options['page'] ?? 1;
        $categorySlug = $options['categorySlug'] ?? null;

        // 1. Define Fields
        if ($mode === 'minimal') {
            $select = 'p.id, p.title, p.permalink, p.updated_at';
        } elseif ($mode === 'summary') {
            $select = 'p.id, p.title, p.permalink, p.updated_at, p.stock_status, p.regular_price';
        } else {
            $select = 'p.*';
        }

        $builder = $this->db->table('products p')->select($select);

        // 2. Category Filter
        if ($categorySlug) {
            $category = $this->categoryModel->where('slug', $categorySlug)->first();
            if ($category) {
                $productIds = $this->categoryModel->getProductIds($category->id, true);
                if (empty($productIds)) return ['total' => 0, 'products' => []];
                $builder->whereIn('p.id', $productIds);
            }
        }

        // 3. Pagination Logic
        if (!($perPage === 'all' && $mode === 'minimal')) {
            $limit = (int)$perPage;
            $offset = ((int)$page - 1) * $limit;
            $builder->limit($limit, $offset);
        }

        // 4. Execute
        $products = $builder->orderBy('p.updated_at', 'DESC')->get()->getResultArray();
        if (empty($products)) return ['total' => 0, 'products' => []];

        // 5. Bulk Fetch Media (Summary/Full only)
        $mediaMap = [];
        if ($mode !== 'minimal') {
            $targetIds = array_column($products, 'id');
            $roles = ($mode === 'summary') ? ['thumbnail'] : ['thumbnail', 'gallery'];
            $mediaMap = $this->mediaModel->getForEntities('product', $targetIds, $roles);
            
            // Apply Sorter
            $products = ProductSorter::sort($products);
        }

        // 6. Transformation Loop
        $finalProducts = [];
        foreach ($products as $product) {
            // Clean Permalink
            if (!empty($product['permalink'])) {
                $cleanPath = preg_replace('/^\/?(product|products)\/?/', '', $product['permalink']);
                $product['permalink'] = trim($cleanPath, '/');
            }

            // Attach Images
            if ($mode !== 'minimal') {
                $media = $mediaMap[$product['id']] ?? ['thumbnail' => null, 'gallery' => []];
                if ($mode === 'summary') $media['gallery'] = [];
                
                $product['images'] = $this->mediaModel->getFlatImages($media, $mode);
            }

            // Cleanup
            foreach (array_keys($product) as $key) {
                if (strpos($key, 'wc_') === 0) unset($product[$key]);
            }
            unset($product['id'], $product['thumb_id']);

            $finalProducts[] = $product;
        }

        return [
            'total'    => count($finalProducts),
            'products' => $finalProducts
        ];
    }
}