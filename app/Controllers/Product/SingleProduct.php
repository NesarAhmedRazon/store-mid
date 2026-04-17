<?php

/*
 * directory: app/Controllers/Product/SingleProduct.php
 * description: Returns a single product by its internal ID or permalink slug.
 *
 * Route: GET /api/get/product/{id_or_slug}
 *   - Numeric segment  → lookup by products.id
 *   - String segment   → lookup by the trailing slug of products.permalink
 *                        (handles both full-URL and path-only storage formats)
 *
 * Query params:
 *   mode=minimal  → title, permalink, stock_status, regular_price, sale_price
 *   mode=summary  → above + thumbnail image, categories (names only)
 *   mode=full     → above + gallery, all attributes, meta, category permalinks  (default)
 */

namespace App\Controllers\Product;

use CodeIgniter\RESTful\ResourceController;
use App\Models\MediaModel;
use App\Models\CategoryModel;
use App\Models\MetaModel;

class SingleProduct extends ResourceController
{
    protected $format = 'json';

    public function show($identifier = null)
    {
        // ------------------------------------------------------------------
        // 1. Validate identifier
        // ------------------------------------------------------------------
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Product identifier is required.',
            ], 400);
        }

        // ------------------------------------------------------------------
        // 2. Mode
        // ------------------------------------------------------------------
        $modeInput = $this->request->getGet('mode') ?? 'full';
        $mode      = in_array($modeInput, ['minimal', 'summary', 'full']) ? $modeInput : 'full';

        // ------------------------------------------------------------------
        // 3. Resolve product row
        // ------------------------------------------------------------------
        $db = \Config\Database::connect();

        $builder = $db->table('products p')->select('p.*');

        if (is_numeric($identifier)) {
            // Numeric → direct PK lookup
            $builder->where('p.id', (int) $identifier);
        } else {
            // Slug → match the last segment of the stored permalink.
            // Handles both "https://store.com/product/resistor-10k" and "/product/resistor-10k".
            $builder->where("SUBSTRING_INDEX(p.permalink, '/', -1)", $identifier);
        }

        $product = $builder->get()->getRowArray();

        if (!$product) {
            return $this->respond([
                'status'  => 'error',
                'message' => "Product '{$identifier}' not found.",
            ], 404);
        }

        $productId = (int) $product['id'];

        // ------------------------------------------------------------------
        // 4. Clean permalink  (mirrors ProductFetcher / EndpointProduct logic)
        //    "https://store.com/product/resistor-10k" → "resistor-10k"
        //    "/product/resistor-10k"                  → "resistor-10k"
        // ------------------------------------------------------------------
        if (!empty($product['permalink'])) {
            $clean               = preg_replace('/^\/?(product|products)\//', '', parse_url($product['permalink'], PHP_URL_PATH) ?? $product['permalink']);
            $product['permalink'] = trim($clean, '/');
        }

        // ------------------------------------------------------------------
        // 5. Attach data by mode
        // ------------------------------------------------------------------
        $mediaModel    = new MediaModel();
        $categoryModel = new CategoryModel();

        // ── Images ───────────────────────────────────────────────────────
        if ($mode === 'minimal') {
            // No images for minimal
        } elseif ($mode === 'summary') {
            $media               = $mediaModel->getForEntity('product', $productId);
            $media['gallery']    = []; // thumbnail only in summary
            $product['images']   = $mediaModel->getFlatImages($media, $mode);
        } else {
            // full — thumbnail + gallery
            $media               = $mediaModel->getForEntity('product', $productId);
            $product['images']   = $mediaModel->getFlatImages($media, $mode);
        }

        // ── Attributes (summary + full) ───────────────────────────────────
        if ($mode !== 'minimal') {
            $product['attributes'] = $this->fetchAttributes($db, $productId);
        }

        // ── Categories ────────────────────────────────────────────────────
        if ($mode !== 'minimal') {
            $rawCategories = $categoryModel->getByProduct($productId);
            $product['categories'] = $this->formatCategories($rawCategories, $mode, $categoryModel);
        }

        // ── Meta (full only) ─────────────────────────────────────────────
        if ($mode === 'full') {
            $metaModel        = new MetaModel();
            $product['meta']  = $metaModel->getMap(MetaModel::ENTITY_PRODUCT, $productId);
        }

        // ------------------------------------------------------------------
        // 6. Cleanup — strip internal / WC-only fields
        // ------------------------------------------------------------------
        foreach (array_keys($product) as $key) {
            if (strpos($key, 'wc_') === 0) {
                unset($product[$key]);
            }
        }
        unset($product['id'], $product['thumb_id'], $product['cost']);

        // ------------------------------------------------------------------
        // 7. Respond
        // ------------------------------------------------------------------
        return $this->respond([
            'status'  => 'ok',
            'mode'    => $mode,
            'product' => $product,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Fetch attributes for one product.
     * Returns: [ 'Resistance' => ['10Ω', '100Ω'], 'Tolerance' => ['5%'] ]
     */
    private function fetchAttributes(\CodeIgniter\Database\BaseConnection $db, int $productId): array
    {
        $rows = $db->table('product_attribute_map pam')
            ->select('pa.name AS attribute, pav.name AS value')
            ->join('product_attributes pa',        'pa.id  = pam.attribute_id')
            ->join('product_attribute_values pav', 'pav.id = pam.value_id')
            ->where('pam.product_id', $productId)
            ->orderBy('pa.name', 'ASC')
            ->get()
            ->getResultArray();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['attribute']][] = $row['value'];
        }

        return $grouped;
    }

    /**
     * Format category rows for the response.
     *
     * summary → [ ['name' => 'Resistors', 'slug' => 'resistors', 'is_primary' => true], … ]
     * full    → above + 'permalink' built from materialized path slugs
     */
    private function formatCategories(array $rawCategories, string $mode, CategoryModel $categoryModel): array
    {
        if (empty($rawCategories)) {
            return [];
        }

        // Collect all ancestor IDs needed for permalink resolution (full mode only)
        $slugMap = [];
        if ($mode === 'full') {
            $allIds = [];
            foreach ($rawCategories as $cat) {
                foreach (explode('/', $cat->path) as $pid) {
                    $allIds[] = (int) $pid;
                }
            }
            $slugRows = $categoryModel->select('id, slug')->whereIn('id', array_unique($allIds))->findAll();
            foreach ($slugRows as $row) {
                $slugMap[(int) $row->id] = $row->slug;
            }
        }

        $result = [];
        foreach ($rawCategories as $cat) {
            $entry = [
                'name'       => $cat->name,
                'slug'       => $cat->slug,
                'is_primary' => (bool) ($cat->is_primary ?? false),
            ];

            if ($mode === 'full') {
                $parts = [];
                foreach (explode('/', $cat->path) as $pid) {
                    $parts[] = $slugMap[(int) $pid] ?? 'unknown';
                }
                $entry['permalink'] = implode('/', $parts);
            }

            $result[] = $entry;
        }

        return $result;
    }
}