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
 *   mode=minimal  → title, permalink, updated_at
 *   mode=summary  → above + thumbnail, brand, mfr, package, price, lcscId, stock, docs
 *   mode=full     → above + gallery, all meta data
 */

namespace App\Controllers\Product;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\ProductFetcher;

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
        // 2. Get mode parameter
        // ------------------------------------------------------------------
        $modeInput = $this->request->getGet('mode') ?? 'full';
        $mode      = in_array($modeInput, ['minimal', 'summary', 'full']) ? $modeInput : 'full';
        $includeMeta = ($mode === 'full');

        // ------------------------------------------------------------------
        // 3. Resolve product ID from identifier
        // ------------------------------------------------------------------
        $db = \Config\Database::connect();
        $productId = null;

        if (is_numeric($identifier)) {
            // Numeric → direct ID
            $productId = (int) $identifier;
        } else {
            // Slug → find product by permalink last segment
            $builder = $db->table('products p')
                ->select('p.id')
                ->where("SUBSTRING_INDEX(p.permalink, '/', -1)", $identifier);
            
            $result = $builder->get()->getRowArray();
            
            if ($result) {
                $productId = (int) $result['id'];
            }
        }

        if (!$productId) {
            return $this->respond([
                'status'  => 'error',
                'message' => "Product '{$identifier}' not found.",
            ], 404);
        }

        // ------------------------------------------------------------------
        // 4. Fetch product using ProductFetcher library
        // ------------------------------------------------------------------
        $productFetcher = new ProductFetcher();
        $product = $productFetcher->getProduct($productId, [
            'mode'        => $mode,
            'includeMeta' => $includeMeta,
        ]);

        if (!$product) {
            return $this->respond([
                'status'  => 'error',
                'message' => "Product '{$identifier}' not found.",
            ], 404);
        }

        // ------------------------------------------------------------------
        // 5. Add categories to the response
        //    (ProductFetcher doesn't include categories by default)
        // ------------------------------------------------------------------
        if ($mode !== 'minimal') {
            $categoryModel = new \App\Models\CategoryModel();
            $rawCategories = $categoryModel->getByProduct($productId);
            $product['categories'] = $this->formatCategories($rawCategories, $mode, $categoryModel);
        }

        // ------------------------------------------------------------------
        // 6. Add attributes to the response (for summary and full modes)
        //    (ProductFetcher doesn't include raw attributes by default)
        // ------------------------------------------------------------------
        if ($mode !== 'minimal') {
            $product['attributes'] = $this->fetchAttributes($db, $productId);
        }
        // ------------------------------------------------------------------
        // 7. Add code snippets (full mode only)
        // ------------------------------------------------------------------
        if ($mode === 'full') {
            $codeModel           = new \App\Models\CodeModel();
            $snippets            = $codeModel->getByProduct($productId);
            $product['programming'] = $codeModel->formatForApi($snippets);
        }
        // ------------------------------------------------------------------
        // 8. Respond
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
    private function formatCategories(array $rawCategories, string $mode, \App\Models\CategoryModel $categoryModel): array
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