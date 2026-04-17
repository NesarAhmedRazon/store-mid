<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use CodeIgniter\RESTful\ResourceController;

class EndpointCategory extends ResourceController
{
    public function send()
    {
        $categoryModel = new CategoryModel();

        // ------------------------------------------------------------------
        // 1. Inputs
        // ------------------------------------------------------------------
        $modeInput = $this->request->getVar('mode');
        $perPage   = max(1, (int) ($this->request->getVar('perPage') ?? 20));
        $pageInput = $this->request->getVar('page') ?? '1';   // may be "all" or a number
        $parent    = $this->request->getVar('parent');         // filter by parent slug

        $mode  = in_array($modeInput, ['minimal', 'summary', 'full']) ? $modeInput : 'full';
        $isAll = ($pageInput === 'all');
        $page  = $isAll ? 1 : max(1, (int) $pageInput);

        // ------------------------------------------------------------------
        // 2. Build Query
        // ------------------------------------------------------------------
        $builder = $categoryModel->builder('product_categories pc');

        if ($mode === 'minimal') {
            $builder->select('pc.id, pc.name, pc.slug, pc.path');
        } elseif ($mode === 'summary') {
            $builder->select('pc.id, pc.name, pc.slug, pc.description, pc.parent_id, pc.updated_at, pc.path');
        } else {
            $builder->select('pc.id, pc.name, pc.slug, pc.description, pc.depth, pc.parent_id, pc.updated_at, pc.path');
        }

        // Filter by parent slug — resolve slug to id first
        if (!empty($parent)) {
            $parentRow = $categoryModel->where('slug', $parent)->first();
            if ($parentRow) {
                $builder->where('pc.parent_id', $parentRow->id);
            } else {
                // Unknown parent slug → return empty result immediately
                return $this->respond([
                    'status'     => 'ok',
                    'view'       => $mode,
                    'page'       => $isAll ? 'all' : $page,
                    'perPage'    => $perPage,
                    'total'      => 0,
                    'categories' => [],
                ]);
            }
        }

        // ------------------------------------------------------------------
        // 3. Count + Fetch
        // ------------------------------------------------------------------
        $total = $builder->countAllResults(false);

        $builder->orderBy('pc.path', 'ASC');

        if ($isAll) {
            // No LIMIT — return everything
            $categories = $builder->get()->getResultArray();
        } else {
            $categories = $builder->limit($perPage, ($page - 1) * $perPage)
                                  ->get()
                                  ->getResultArray();
        }

        if (empty($categories)) {
            return $this->respond([
                'status'     => 'ok',
                'view'       => $mode,
                'page'       => $isAll ? 'all' : $page,
                'perPage'    => $perPage,
                'total'      => 0,
                'categories' => [],
            ]);
        }

        // ------------------------------------------------------------------
        // 4. Attach total_products using one bulk query (O(1) lookups)
        // ------------------------------------------------------------------
        $productCounts = $categoryModel->getProductCountsAll();

        // ------------------------------------------------------------------
        // 5. Hierarchical permalink transformation + total_products injection
        // ------------------------------------------------------------------
        $finalCategories = $this->attachPermalinks($categories, $categoryModel, $productCounts);

        return $this->respond([
            'status'     => 'ok',
            'view'       => $mode,
            'page'       => $isAll ? 'all' : $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'categories' => $finalCategories,
        ]);
    }

    public function categoryBySlug(string $categorySlug = null): \CodeIgniter\HTTP\ResponseInterface{
        /**
         * This will return category information by slug. 
         * it will support return id,title(name),parmalink,description,products[]
         * if mode is not minimal then only add products(summary/minimal), also need support for modes.
         * need to return page,perPage,total.
         * if no category provided handle with proper error respons
         */
    }
    /**
     * Converts the numeric path (1/5/12) into a slug-based permalink
     * (electronics/computers/laptops) and injects total_products per category.
     *
     * @param array         $categories    Raw rows from DB
     * @param CategoryModel $model
     * @param array         $productCounts [ category_id => count ] from getProductCountsAll()
     */
    private function attachPermalinks(array $categories, CategoryModel $model, array $productCounts = []): array
    {
        // Collect every ID referenced in any path — one round-trip to DB
        $allIds = [];
        foreach ($categories as $cat) {
            foreach (explode('/', $cat['path']) as $id) {
                $allIds[] = (int) $id;
            }
        }
        $allIds = array_unique($allIds);

        // Build slug lookup map: [ id => slug ]
        $slugMap = [];
        if (!empty($allIds)) {
            $slugData = $model->select('id, slug')->whereIn('id', $allIds)->findAll();
            foreach ($slugData as $row) {
                $slugMap[(int) $row->id] = $row->slug;
            }
        }

        foreach ($categories as &$cat) {
            // Transform "1/5/12" → "electronics/computers/laptops"
            $permalinkParts = [];
            foreach (explode('/', $cat['path']) as $id) {
                $permalinkParts[] = $slugMap[(int) $id] ?? 'unknown';
            }
            $cat['permalink']      = implode('/', $permalinkParts);
            $cat['total_products'] = $productCounts[(int) $cat['id']] ?? 0;

            unset($cat['path']);
        }

        return $categories;
    }
}

