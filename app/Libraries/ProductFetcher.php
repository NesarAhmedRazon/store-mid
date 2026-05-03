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
        $this->db            = \Config\Database::connect();
        $this->mediaModel    = new MediaModel();
        $this->categoryModel = new CategoryModel();
        $this->metaModel     = new MetaModel();
    }

    // ── Summary extra fields ──────────────────────────────────────────────
    // Attribute names as stored in product_attributes.name (from WC)
    private const ATTR_BRAND    = 'brand';                       // → brand
    private const ATTR_MFR      = 'manufacturer-part-number';    // → mfr
    private const ATTR_PKG      = 'package-size';                // → package/size
    private const ATTR_LCSC     = 'easyeda-id';                  // → lcscId

    /**
     * Get multiple products with pagination
     */
    public function getProducts(array $options = []): array
    {
        $mode         = $options['mode'] ?? 'full';
        $perPage      = $options['perPage'] ?? 20;
        $page         = $options['page'] ?? 1;
        $categorySlug = $options['categorySlug'] ?? null;
        $includeMeta  = $options['includeMeta'] ?? ($mode === 'full');
        $sortDirection = $options['sortDirection'] ?? 'newest-first';
        $filterZeroPrice = $options['filterZeroPrice'] ?? true;

        // ------------------------------------------------------------------
        // 1. Define Fields
        // ------------------------------------------------------------------
        if ($mode === 'minimal') {
            $select = 'p.id, p.title, p.permalink, p.updated_at';
        } elseif ($mode === 'summary') {
            $select = 'p.id, p.title, p.sku, p.permalink, p.updated_at, p.stock_status, p.stock_quantity, p.stock_unit, p.price_regular, p.price_offer, p.price_buy, p.price_sell';
        } else {
            $select = 'p.*';
        }

        $builder = $this->db->table('products p')->select($select);

        // ------------------------------------------------------------------
        // 2. Category Filter
        // ------------------------------------------------------------------
        if ($categorySlug) {
            $category = $this->categoryModel->where('slug', $categorySlug)->first();
            if ($category) {
                $productIds = $this->categoryModel->getProductIds($category->id, true);
                if (empty($productIds)) return ['total' => 0, 'products' => []];
                $builder->whereIn('p.id', $productIds);
            }
        }

        // ------------------------------------------------------------------
        // 3. True total — COUNT before applying LIMIT
        // ------------------------------------------------------------------
        $total = $builder->countAllResults(false);
        if ($total === 0) return ['total' => 0, 'products' => []];

        // ------------------------------------------------------------------
        // 4. Pagination
        // ------------------------------------------------------------------
        if ($perPage !== 'all') {
            $limit  = max(1, (int) $perPage);
            $offset = (max(1, (int) $page) - 1) * $limit;
            $builder->limit($limit, $offset);
        }

        // ------------------------------------------------------------------
        // 5. Execute
        // ------------------------------------------------------------------
        $products = $builder->orderBy('p.updated_at', 'DESC')->get()->getResultArray();
        if (empty($products)) return ['total' => $total, 'products' => []];

        $targetIds = array_column($products, 'id');

        // ------------------------------------------------------------------
        // 6. Bulk side-loads
        // ------------------------------------------------------------------
        $sideLoads = $this->loadSideData($targetIds, $mode);

        // ------------------------------------------------------------------
        // 7. Sort products using ProductSorter (BEFORE transformation)
        // ------------------------------------------------------------------
        $products = ProductSorter::sort($products, $filterZeroPrice, $sortDirection);

        // Re-extract IDs after sorting (in case order changed or items were filtered)
        $targetIds = array_column($products, 'id');

        // Reorganize side-loads to match the sorted/filtered product list
        $sideLoads = $this->reorganizeSideLoads($sideLoads, $targetIds);

        // ------------------------------------------------------------------
        // 8. Transform each product using shared method
        // ------------------------------------------------------------------
        $finalProducts = [];
        foreach ($products as $product) {
            $finalProducts[] = $this->transformProduct($product, $sideLoads, $mode, $includeMeta);
        }

        return [
            'total'    => $total,
            'products' => $finalProducts,
        ];
    }

    /**
     * Get a single product by ID
     */
    public function getProduct(int $productId, array $options = []): ?array
    {
        
        $mode           = $options['mode'] ?? 'full';
        $includeMeta    = $options['includeMeta'] ?? ($mode === 'full');
        $internal       = $options['internal'] ?? false;

        // ------------------------------------------------------------------
        // 1. Fetch the product
        // ------------------------------------------------------------------
        if ($mode === 'minimal') {
            $select = 'p.id, p.title, p.permalink, p.updated_at';
        } elseif ($mode === 'summary') {
            $select = 'p.id, p.title, p.sku, p.permalink, p.updated_at, p.stock_status, p.stock_quantity, p.stock_unit, p.price_regular, p.price_offer, p.price_buy, p.price_sell';
        } else {
            $select = 'p.*';
        }

        $product = $this->db->table('products p')
            ->select($select)
            ->where('p.id', $productId)
            ->get()
            ->getRowArray();

        if (!$product) return null;

        // ------------------------------------------------------------------
        // 2. Load side data for this single product
        // ------------------------------------------------------------------
        $sideLoads = $this->loadSideData([$productId], $mode);

        // ------------------------------------------------------------------
        // 3. Transform using shared method
        // ------------------------------------------------------------------
        return $this->transformProduct($product, $sideLoads, $mode, $includeMeta,$internal);
    }

    /**
     * Reorganize side-loads to only include products that survived sorting/filtering
     */
    private function reorganizeSideLoads(array $sideLoads, array $targetIds): array
    {
        $reorganized = [
            'mediaMap'    => [],
            'summaryAttr' => [],
            'docsMap'     => [],
            'metadataMap' => [],
        ];

        foreach ($targetIds as $id) {
            if (isset($sideLoads['mediaMap'][$id])) {
                $reorganized['mediaMap'][$id] = $sideLoads['mediaMap'][$id];
            }
            if (isset($sideLoads['summaryAttr'][$id])) {
                $reorganized['summaryAttr'][$id] = $sideLoads['summaryAttr'][$id];
            }
            if (isset($sideLoads['docsMap'][$id])) {
                $reorganized['docsMap'][$id] = $sideLoads['docsMap'][$id];
            }
            if (isset($sideLoads['metadataMap'][$id])) {
                $reorganized['metadataMap'][$id] = $sideLoads['metadataMap'][$id];
            }
        }

        return $reorganized;
    }

    /**
     * Load all side data needed for product transformation (media, attributes, meta, docs)
     */
    private function loadSideData(array $productIds, string $mode): array
    {
        $sideLoads = [
            'mediaMap'    => [],
            'summaryAttr' => [],
            'docsMap'     => [],
            'metadataMap' => [],
        ];

        if (empty($productIds)) {
            return $sideLoads;
        }

        if ($mode === 'minimal') {
            return $sideLoads;
        }

        // Media
        $roles = ($mode === 'summary') ? ['thumbnail'] : ['thumbnail', 'gallery'];
        $sideLoads['mediaMap'] = $this->mediaModel->getForEntities('product', $productIds, $roles);

        // Summary extra attributes
        if ($mode === 'summary') {
            $sideLoads['summaryAttr'] = $this->bulkGetNamedAttributes(
                $productIds,
                [self::ATTR_BRAND, self::ATTR_LCSC, self::ATTR_PKG, self::ATTR_MFR]
            );
            $sideLoads['docsMap'] = $this->bulkGetMetaKey($productIds, 'extra_documents');
        }

        // Full metadata
        if ($mode === 'full') {
            $sideLoads['metadataMap'] = $this->metaModel->getMapBulk('product', $productIds);
        }

        return $sideLoads;
    }

    /**
     * Transform a single product record into the standardized output format
     * This is the SINGLE SOURCE OF TRUTH for product data structure
     */
    private function transformProduct(array $product, array $sideLoads, string $mode, bool $includeMeta,bool $internal = false): array
    {
        $pid = $product['id'];
        $product['sku'] = $product['sku'];

        // ------------------------------------------------------------------
        // Clean permalink
        // ------------------------------------------------------------------
        if (!empty($product['permalink'])) {
            $cleanPath = preg_replace('/^\/?(product|products)\/?/', '', $product['permalink']);
            $product['permalink'] = trim($cleanPath, '/');
        }

        // ------------------------------------------------------------------
        // Images
        // ------------------------------------------------------------------
        if ($mode !== 'minimal') {
            $media = $sideLoads['mediaMap'][$pid] ?? ['thumbnail' => null, 'gallery' => []];
            if ($mode === 'summary') $media['gallery'] = [];
            $product['images'] = $this->mediaModel->getFlatImages($media, $mode);
        }

        // ------------------------------------------------------------------
        // Summary extras (brand, mfr, package, price, lcscId)
        // ------------------------------------------------------------------
        if ($mode === 'summary') {
            $attrs = $sideLoads['summaryAttr'][$pid] ?? [];
            $product['brand']   = $attrs[self::ATTR_BRAND] ?? null;
            $product['mfr']     = $attrs[self::ATTR_MFR] ?? null;
            $product['package'] = $attrs[self::ATTR_PKG] ?? null; 
            $product['lcscId']  = $attrs[self::ATTR_LCSC] ?? null;
        }

        

        // ------------------------------------------------------------------
        // Stock and Price information (for all non-minimal modes)
        // ------------------------------------------------------------------
        if ($mode !== 'minimal') {
            $product['docs'] = $sideLoads['docsMap'][$pid] ?? null;
            $product['stock'] = [
                'unit'     => $product['stock_unit']    ?? null,
                'status'   => $product['stock_status']  ?? null,
                'quantity' => isset($product['stock_quantity']) ? (float) $product['stock_quantity'] : null,
            ];

            // Remove raw stock fields to avoid duplication
            unset($product['stock_unit'], $product['stock_status'], $product['stock_quantity']);

            // ---------------------------
            // Pricing
            // ---------------------------

            $selling_price = $product['price_sell'] ?? 0;
            $regular_price = isset($product['price_regular']) ? $product['price_regular'] : 0;
            $offer_price = isset($product['price_offer']) ? (float) $product['price_offer'] : null;
            $buying_price = isset($product['price_buy']) ? $product['price_buy'] : 0;
            
            $product['price'] = [
                'sell'   => (float) $selling_price,
                'regular'   => (float) $regular_price,
                'offer'     => $offer_price,                
            ];
            if($internal){
                $product['price']['cost'] = (float) $buying_price;
            }
            // Remove raw price fields
            unset($product['price_sell'], $product['price_buy'],$product['price_regular'],$product['price_offer']);
        }

        // ------------------------------------------------------------------
        // Full metadata
        // ------------------------------------------------------------------
        if ($includeMeta && $mode === 'full') {
            $product['metadata'] = $sideLoads['metadataMap'][$pid] ?? [];
        }

        // ------------------------------------------------------------------
        // Strip internal fields
        // ------------------------------------------------------------------
        if(!$internal){
            foreach (array_keys($product) as $key) {
                if (strpos($key, 'wc_') === 0) unset($product[$key]);
            }
        }
        unset($product['thumb_id']);

        return $product;
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Bulk-fetch specific named attributes for a set of product IDs.
     */
    private function bulkGetNamedAttributes(array $productIds, array $attributeNames): array
    {
        if (empty($productIds) || empty($attributeNames)) return [];

        $rows = $this->db->table('product_attribute_map pam')
            ->select('pam.product_id, pa.name AS attr_name, pav.name AS attr_value')
            ->join('product_attributes pa',        'pa.id  = pam.attribute_id')
            ->join('product_attribute_values pav', 'pav.id = pam.value_id')
            ->whereIn('pam.product_id', $productIds)
            ->whereIn('pa.name',        $attributeNames)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $pid  = (int) $row['product_id'];
            $name = $row['attr_name'];
            if (!isset($map[$pid][$name])) {
                $map[$pid][$name] = $row['attr_value'];
            }
        }

        return $map;
    }

    /**
     * Bulk-fetch a single meta key for a set of product IDs.
     */
    private function bulkGetMetaKey(array $productIds, string $metaKey): array
    {
        if (empty($productIds)) return [];

        $rows = $this->db->table('meta')
            ->select('entity_id, value')
            ->where('entity_type', 'product')
            ->where('slug', $metaKey)
            ->whereIn('entity_id', $productIds)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $raw = $row['value'];
            if ($raw !== null && $raw !== '') {
                $trimmed = ltrim((string) $raw);
                if ($trimmed[0] === '{' || $trimmed[0] === '[') {
                    $decoded = json_decode($raw, true);
                    $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
                }
            }
            $map[(int) $row['entity_id']] = $raw;
        }

        return $map;
    }
}