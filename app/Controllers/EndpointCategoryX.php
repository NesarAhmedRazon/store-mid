<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CategoryModel;
use App\Libraries\ProductFetcher;

class EndpointCategoryX extends ResourceController
{
    protected $format = 'json';

    /**
     * GET /api/get/categories
     * Returns a list of categories. Auth is handled by ApiAuthFilter.
     */
    public function send(): \CodeIgniter\HTTP\ResponseInterface
    {
        $categoryModel = new CategoryModel();

        // 1. Gather Inputs
        $modeInput = $this->request->getVar('mode');
        $perPage   = max(1, (int) ($this->request->getVar('per_page') ?? 20));
        $pageInput = $this->request->getVar('page') ?? '1';
        $parent    = $this->request->getVar('parent');

        $mode  = in_array($modeInput, ['minimal', 'summary', 'full']) ? $modeInput : 'full';
        $isAll = ($pageInput === 'all');
        $page  = $isAll ? 1 : max(1, (int) $pageInput);

        // 2. Build Query
        $builder = $categoryModel->builder('product_categories pc');
        $this->applySelectMode($builder, $mode);

        // Filter by parent slug — resolve slug to id first
        if (!empty($parent)) {
            $parentRow = $categoryModel->where('slug', $parent)->first();
            if (!$parentRow) {
                return $this->emptyResponse($mode, $isAll ? 'all' : $page, $perPage);
            }
            $builder->where('pc.parent_id', $parentRow->id);
        }

        // 3. Count + Fetch
        $total = $builder->countAllResults(false);
        $builder->orderBy('pc.path', 'ASC');

        if (!$isAll) {
            $builder->limit($perPage, ($page - 1) * $perPage);
        }

        $categories = $builder->get()->getResultArray();

        if (empty($categories)) {
            return $this->emptyResponse($mode, $isAll ? 'all' : $page, $perPage);
        }

        // 4. Enrich Data
        $productCounts   = $categoryModel->getProductCountsAll();
        $finalCategories = $this->attachPermalinks($categories, $categoryModel, $productCounts);

        return $this->respond([
            'mode'       => $mode,
            'page'       => $isAll ? 'all' : $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'categories' => $finalCategories,
        ]);
    }

    /**
     * GET /api/get/category/{slug}
     * Returns specific category data + its products using the ProductFetcher library.
     */
    public function categoryBySlug(string $categorySlug = null): \CodeIgniter\HTTP\ResponseInterface
    {
        if (empty($categorySlug)) {
            return $this->fail('Category slug is required', 400);
        }

        $categoryModel = new CategoryModel();
        $category = $categoryModel->where('slug', $categorySlug)->first();

        if (!$category) {
            return $this->failNotFound('Category not found');
        }

        $mode    = $this->request->getGet('mode') ?? 'minimal';
        $pMode   = $this->request->getGet('pMode') ?? 'summary';
        $perPage = $this->request->getGet('per_page') ?? 20;
        $page    = $this->request->getGet('page') ?? 1;

        // Fetch Products logic handled by Library
        $products = [];
        $totalProducts = 0;


        $fetcher = new ProductFetcher();
        $result = $fetcher->getProducts([
            'categorySlug' => $categorySlug,
            'mode'         => $pMode,
            'perPage'      => $perPage,
            'page'         => $page
        ]);


        // 3. Construct the specific productData shape
        $productData = [
            'total' => $result['total'] // Corrected 'totla' typo to 'total'
        ];
        // Only attach the actual product array if we aren't in minimal mode
        if ($mode !== 'minimal') {
            $productData['data'] = $result['products'];
        }

        return $this->respond([
            'page'     => (int)$page,
            'perPage'  => (int)$perPage,
            'total'    => $totalProducts,
            'category' => [
                'id'          => $category->id,
                'title'        => $category->name,
                'permalink'   => $this->resolvePathToPermalink($category->path, $categoryModel),
                'description' => $category->description ?? '',
                'parent_id'   => $category->parent_id
            ],
            'products' => $productData,
        ]);
    }

    // ── Private Helpers ───────────────────────────────────────────────────

    /**
     * Sets which columns to select based on the view mode.
     */
    private function applySelectMode($builder, string $mode): void
    {
        $fields = [
            'minimal' => 'pc.id, pc.name, pc.slug, pc.path',
            'summary' => 'pc.id, pc.name, pc.slug, pc.description, pc.parent_id, pc.updated_at, pc.path',
            'full'    => 'pc.id, pc.name, pc.slug, pc.description, pc.depth, pc.parent_id, pc.updated_at, pc.path'
        ];
        $builder->select($fields[$mode] ?? $fields['full']);
    }

    /**
     * Standardized empty response format.
     */
    private function emptyResponse(string $mode, $page, int $perPage): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->respond([
            'mode'       => $mode,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => 0,
            'categories' => [],
        ]);
    }

    /**
     * Converts numeric paths to slug-based permalinks.
     */
    private function attachPermalinks(array $categories, CategoryModel $model, array $productCounts = []): array
    {
        $allIds = [];
        foreach ($categories as $cat) {
            if (empty($cat['path'])) continue;
            foreach (explode('/', $cat['path']) as $id) {
                $allIds[] = (int) $id;
            }
        }
        $allIds = array_unique($allIds);

        $slugMap = [];
        if (!empty($allIds)) {
            $slugData = $model->select('id, slug')->whereIn('id', $allIds)->findAll();
            foreach ($slugData as $row) {
                $slugMap[(int) $row->id] = $row->slug;
            }
        }

        foreach ($categories as &$cat) {
            $permalinkParts = [];
            if (!empty($cat['path'])) {
                foreach (explode('/', $cat['path']) as $id) {
                    $permalinkParts[] = $slugMap[(int) $id] ?? 'unknown';
                }
            }
            $cat['permalink']      = implode('/', $permalinkParts);
            $cat['total_products'] = $productCounts[(int) $cat['id']] ?? 0;
            unset($cat['path']);
        }

        return $categories;
    }

    private function resolvePathToPermalink(string $path, CategoryModel $model): string
    {
        if (empty($path)) return 'unknown';

        $ids = explode('/', $path);
        $slugData = $model->select('id, slug')->whereIn('id', $ids)->findAll();

        // Create a lookup map
        $slugMap = [];
        foreach ($slugData as $row) {
            $slugMap[(int) $row->id] = $row->slug;
        }

        // Reconstruct the path using slugs in the original order
        $permalinkParts = [];
        foreach ($ids as $id) {
            $permalinkParts[] = $slugMap[(int) $id] ?? 'unknown';
        }

        return implode('/', $permalinkParts);
    }
}
