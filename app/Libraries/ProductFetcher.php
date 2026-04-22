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
    private const ATTR_BRAND    = 'brand';       // → brand
    private const ATTR_MFR      = 'manufacturer-part-number';       // → mfr
    private const ATTR_PKG      = 'package-size';       // → package/size
    private const ATTR_LCSC     = 'easyeda-id';  // → lcscId

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

        // ------------------------------------------------------------------
        // 1. Define Fields
        // ------------------------------------------------------------------
        if ($mode === 'minimal') {
            $select = 'p.id, p.title, p.permalink, p.updated_at';
        } elseif ($mode === 'summary') {
            // regular_price + sale_price come from the products table directly
            $select = 'p.id, p.title, p.permalink, p.updated_at, p.stock_status,p.stock_quantity, p.regular_price, p.sale_price';
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
        // 4. Pagination — skip LIMIT entirely when perPage=all
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
        // 6. Bulk side-loads (one query each, never N+1)
        // ------------------------------------------------------------------
        $mediaMap    = [];
        $summaryAttr = [];   // [ product_id => [ 'mfr' => '...', 'lcscId' => '...' ] ]
        $metadataMap = [];
        $docsMap     = [];   // [ product_id => decoded extra_documents value ]

        if ($mode !== 'minimal') {
            // Media
            $roles    = ($mode === 'summary') ? ['thumbnail'] : ['thumbnail', 'gallery'];
            $mediaMap = $this->mediaModel->getForEntities('product', $targetIds, $roles);

            // Sort
            $products = ProductSorter::sort($products);

            // Summary extra: mfr (brand) + lcscId (easyeda-id) — one JOIN query
            if ($mode === 'summary') {
                $summaryAttr = $this->bulkGetNamedAttributes(
                    $targetIds,
                    [self::ATTR_BRAND, self::ATTR_LCSC,self::ATTR_PKG,self::ATTR_MFR]
                );

                // docs from meta — extra_documents key only
                $docsMap = $this->bulkGetMetaKey($targetIds, 'extra_documents');
            }
        }

        if ($includeMeta && $mode === 'full') {
            $metadataMap = $this->metaModel->getMapBulk('product', $targetIds);
        }

        // ------------------------------------------------------------------
        // 7. Transformation loop
        // ------------------------------------------------------------------
        $finalProducts = [];
        foreach ($products as $product) {
            $pid = $product['id'];

            // Clean permalink
            if (!empty($product['permalink'])) {
                $cleanPath           = preg_replace('/^\/?(product|products)\/?/', '', $product['permalink']);
                $product['permalink'] = trim($cleanPath, '/');
            }

            // Images
            if ($mode !== 'minimal') {
                $media = $mediaMap[$pid] ?? ['thumbnail' => null, 'gallery' => []];
                if ($mode === 'summary') $media['gallery'] = [];
                $product['images'] = $this->mediaModel->getFlatImages($media, $mode);
            }
            
            // Summary extras
            if ($mode === 'summary') {
                error_log('we are in product fetch '.$categorySlug);
                $attrs = $summaryAttr[$pid] ?? [];

                $product['brand']   = $attrs[self::ATTR_BRAND] ?? null;
                $product['mfr']     = $attrs[self::ATTR_MFR] ?? null;                
                $product['package'] = $attrs[self::ATTR_PKG] ?? null;
                $product['price']   = $product['sale_price'] ? (float)$product['regular_price'] : (float)$product['regular_price'];
                $product['lcscId']  = $attrs[self::ATTR_LCSC]  ?? null;
                $product['docs']    = $docsMap[$pid]           ?? null;

                // remove the raw sale_price key — exposed as salePrice above
                unset($product['sale_price']);
            }

            // Full metadata
            if ($includeMeta && $mode === 'full') {
                $product['metadata'] = $metadataMap[$pid] ?? [];
            }

            // Strip internal fields
            foreach (array_keys($product) as $key) {
                if (strpos($key, 'wc_') === 0) unset($product[$key]);
            }
            unset($product['id'], $product['thumb_id']);

            $finalProducts[] = $product;
        }

        return [
            'total'    => $total,
            'products' => $finalProducts,
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Bulk-fetch specific named attributes for a set of product IDs.
     * Returns: [ product_id => [ attribute_name => first_value ] ]
     *
     * Uses a single JOIN query regardless of how many products or attribute
     * names are requested — no N+1.
     *
     * @param int[]    $productIds
     * @param string[] $attributeNames  Exact values of product_attributes.name
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

        // Build map — keep first value per (product, attribute) pair
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
     * Returns: [ product_id => decoded_value ]
     *
     * Delegates to MetaModel::getMapBulk() which already decodes JSON values,
     * then extracts only the requested key.
     *
     * @param int[]  $productIds
     * @param string $metaKey
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
            // Decode JSON arrays/objects, leave plain strings as-is
            if ($raw !== null && $raw !== '') {
                $trimmed = ltrim((string) $raw);
                if ($trimmed[0] === '{' || $trimmed[0] === '[') {
                    $decoded = json_decode($raw, associative: true);
                    $raw     = json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
                }
            }
            $map[(int) $row['entity_id']] = $raw;
        }

        return $map;
    }
}