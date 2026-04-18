<?php
// /app/Libraries/ProductFetcher.php
namespace App\Libraries;

use App\Models\CategoryModel;
use App\Models\MediaModel;
use App\Models\MetaModel;
use App\Libraries\ProductSorter;

class ProductFetcher
{
    protected $db;
    protected $mediaModel;
    protected $categoryModel;
    protected $metaModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->mediaModel = new MediaModel();
        $this->categoryModel = new CategoryModel();
        $this->metaModel = new MetaModel();
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
        $includeMeta  = $options['includeMeta'] ?? ($mode === 'full');

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

        // 3. True total — COUNT before applying LIMIT
        $total = $builder->countAllResults(false);
        if ($total === 0) return ['total' => 0, 'products' => []];

        // 4. Pagination Logic — skip LIMIT entirely when perPage=all
        if ($perPage !== 'all') {
            $limit  = max(1, (int) $perPage);
            $offset = (max(1, (int) $page) - 1) * $limit;
            $builder->limit($limit, $offset);
        }

        // 5. Execute
        $products = $builder->orderBy('p.updated_at', 'DESC')->get()->getResultArray();
        if (empty($products)) return ['total' => $total, 'products' => []];

        // 5. Bulk Fetch Media (Summary/Full only)
        $mediaMap = [];
        if ($mode !== 'minimal') {
            $targetIds = array_column($products, 'id');
            $roles = ($mode === 'summary') ? ['thumbnail'] : ['thumbnail', 'gallery'];
            $mediaMap = $this->mediaModel->getForEntities('product', $targetIds, $roles);

            // Apply Sorter
            $products = ProductSorter::sort($products);
        }
        // 6. Bulk Fetch Metadata (for full mode)
        $metadataMap = [];
        if ($includeMeta && $mode === 'full') {
            $targetIds = array_column($products, 'id');
            $metadataMap = $this->metaModel->getMapBulk('product', $targetIds);
        }
        // 7. Transformation Loop
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
            // Attach Metadata (full mode only)
            if ($includeMeta && $mode === 'full') {
                $product['metadata'] = $metadataMap[$product['id']] ?? [];
            }

            // Cleanup
            foreach (array_keys($product) as $key) {
                if (strpos($key, 'wc_') === 0) unset($product[$key]);
            }
            unset($product['id'], $product['thumb_id']);

            $finalProducts[] = $product;
        }

        return [
            'total'    => $total,
            'products' => $finalProducts
        ];
    }
}