<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use CodeIgniter\RESTful\ResourceController;

class EndpointCategory extends ResourceController
{
    public function send()
    {
        $categoryModel = new CategoryModel();

        // 1. Inputs
        $view         = $this->request->getVar('view');
        $perPage      = (int) ($this->request->getVar('perPage') ?? 20);
        $page         = (int) ($this->request->getVar('page') ?? 1);
        $categorySlug = $this->request->getVar('categorySlug');

        $mode = in_array($view, ['minimal', 'summary', 'full']) ? $view : 'full';

        // 2. Build Query
        $builder = $categoryModel->builder('product_categories pc');

        // Select logic based on mode
        if ($mode === 'minimal') {
            $builder->select('pc.id, pc.name, pc.slug, pc.path');
        } elseif ($mode === 'summary') {
            $builder->select('pc.id, pc.name, pc.slug, pc.description, pc.depth, pc.updated_at, pc.path');
        } else {
            $builder->select('pc.id, pc.name, pc.slug, pc.description, pc.depth, pc.parent_id, pc.updated_at, pc.path');
        }

        // Filter by slug if provided
        if (!empty($categorySlug)) {
            $builder->where('pc.slug', $categorySlug);
        }

        // 3. Pagination & Fetch
        $total    = $builder->countAllResults(false);
        $categories = $builder->limit($perPage, ($page - 1) * $perPage)
                              ->orderBy('pc.path', 'ASC')
                              ->get()
                              ->getResultArray();

        if (empty($categories)) {
            return $this->respond([
                'status' => 'ok',
                'total'  => 0,
                'categories' => []
            ]);
        }

        // 4. Hierarchical Permalink Transformation
        // We fetch all slugs involved in the paths of the current result set
        $finalCategories = $this->attachPermalinks($categories, $categoryModel);

        return $this->respond([
            'status'     => 'ok',
            'view'       => $mode,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'categories' => $finalCategories,
        ]);
    }

    /**
     * Converts the numeric 'path' (1/5/12) into a slug-based permalink (electronics/computers/laptops)
     */
    private function attachPermalinks(array $categories, CategoryModel $model): array
    {
        // Collect all IDs mentioned in all paths to do one single lookup
        $allIds = [];
        foreach ($categories as $cat) {
            $allIds = array_merge($allIds, explode('/', $cat['path']));
        }
        $allIds = array_unique(array_map('intval', $allIds));

        // Create a lookup map: [id => slug]
        $slugMap = [];
        if (!empty($allIds)) {
            $slugData = $model->select('id, slug')->whereIn('id', $allIds)->findAll();
            foreach ($slugData as $row) {
                $slugMap[$row->id] = $row->slug;
            }
        }

        foreach ($categories as &$cat) {
            // Transform path "1/5/12" -> "slug1/slug2/slug3"
            $pathIds = explode('/', $cat['path']);
            $permalinkParts = [];
            foreach ($pathIds as $id) {
                $permalinkParts[] = $slugMap[$id] ?? 'unknown';
            }

            $cat['permalink'] = implode('/', $permalinkParts);

            // Clean up: Remove internal 'path' and rename 'slug' logic if needed
            unset($cat['path']);
            
            // Per your request: Replace slug with permalink or keep both? 
            // Usually, we keep slug for the "current" part and permalink for the full path.
        }

        return $categories;
    }
}