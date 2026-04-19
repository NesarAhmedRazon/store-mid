<?php

namespace App\Controllers\Product;

use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Controllers\BaseController;
use CodeIgniter\RESTful\ResourceController;

class CategoryController extends BaseController
{
    protected CategoryModel $model;

    // Base URL for all redirects — single source of truth
    private const BASE = '/products/categories';

    public function __construct()
    {
        $this->model = new CategoryModel();
    }

    // ── Index ────────────────────────────────────────────────────────────

    public function index()
    {
        $categories = $this->model->getTree();
        $counts     = $this->model->getProductCountsAll();

        foreach ($categories as $category) {
            $category->product_count = $counts[$category->id] ?? 0;
        }

        return view('admin/categories/index', [
            'title'      => 'Categories',
            'categories' => $categories,
        ]);
    }

    
    // ── Preview ──────────────────────────────────────────────────────────

    public function preview(int $id)
    {
        $category = $this->model->find($id);

        if (!$category) {
            return redirect()->to(self::BASE)->with('error', 'Category not found.');
        }

        $productIds = $this->model->getProductIds($id, true);

        // Bulk fetch — one query, not N+1
        $products = [];
        if (!empty($productIds)) {
            $products = (new ProductModel())
                ->select('id, title, permalink, stock_status, regular_price')
                ->whereIn('id', $productIds)
                ->findAll();
        }

        return view('admin/categories/single/preview', [
            'title'      => $category->name,
            'category'   => $category,
            'breadcrumb' => $this->model->getBreadcrumb($id),
            'children'   => $this->model->getChildren($id),
            'products'   => $products,
        ]);
    }

    // ── Create ───────────────────────────────────────────────────────────

    public function create()
    {
        return view('admin/categories/single/create', [
            'title'             => 'New Category',
            'allCategories'     => $this->model->getTree(),
            'preselectedParent' => (int) $this->request->getGet('parent'),
            'errors'            => [],
        ]);
    }

    // ── Store ────────────────────────────────────────────────────────────

    public function store()
    {
        $rules = [
            'name'      => 'required|min_length[2]|max_length[120]',
            'slug'      => 'required|min_length[2]|max_length[140]|is_unique[product_categories.slug]',
            'parent_id' => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validate($rules)) {
            return view('admin/categories/single/create', [
                'title'             => 'New Category',
                'allCategories'     => $this->model->getTree(),
                'preselectedParent' => (int) $this->request->getPost('parent_id'),
                'errors'            => $this->validator->getErrors(),
            ]);
        }

        $parentId = (int) $this->request->getPost('parent_id') ?: null;

        $this->model->insertWithPath([
            'name'        => $this->request->getPost('name'),
            'slug'        => $this->request->getPost('slug'),
            'description' => $this->request->getPost('description') ?: null,
            'parent_id'   => $parentId,
        ]);

        return redirect()->to(self::BASE)->with('success', 'Category created.');
    }

    // ── Edit ─────────────────────────────────────────────────────────────

    public function edit(int $id)
    {
        $category = $this->model->find($id);

        if (!$category) {
            return redirect()->to(self::BASE)->with('error', 'Category not found.');
        }

        // Exclude self and descendants from parent dropdown
        $all         = $this->model->getTree();
        $descendants = $this->model->getDescendants($id);
        $excludeIds  = array_merge([$id], array_column($descendants, 'id'));
        $available   = array_filter($all, fn($c) => !in_array($c->id, $excludeIds));

        return view('admin/categories/single/edit', [
            'title'         => 'Edit: ' . $category->name,
            'category'      => $category,
            'allCategories' => array_values($available),
            'errors'        => [],
        ]);
    }

    // ── Update ───────────────────────────────────────────────────────────

    public function update(int $id)
    {
        $category = $this->model->find($id);

        if (!$category) {
            return redirect()->to(self::BASE)->with('error', 'Category not found.');
        }

        $rules = [
            'name'      => 'required|min_length[2]|max_length[120]',
            'slug'      => "required|min_length[2]|max_length[140]|is_unique[product_categories.slug,id,{$id}]",
            'parent_id' => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validate($rules)) {
            $all         = $this->model->getTree();
            $descendants = $this->model->getDescendants($id);
            $excludeIds  = array_merge([$id], array_column($descendants, 'id'));
            $available   = array_filter($all, fn($c) => !in_array($c->id, $excludeIds));

            return view('admin/categories/single/edit', [
                'title'         => 'Edit: ' . $category->name,
                'category'      => $category,
                'allCategories' => array_values($available),
                'errors'        => $this->validator->getErrors(),
            ]);
        }

        $newParentId = (int) $this->request->getPost('parent_id') ?: null;

        // If parent changed, moveTo() rebuilds path + depth for subtree
        if ($category->parent_id !== $newParentId) {
            $this->model->moveTo($id, $newParentId);
        }

        $this->model->update($id, [
            'name'        => $this->request->getPost('name'),
            'slug'        => $this->request->getPost('slug'),
            'description' => $this->request->getPost('description') ?: null,
            'parent_id'   => $newParentId,
        ]);

        return redirect()->to(self::BASE . '/' . $id)->with('success', 'Category updated.');
    }

    // ── Import ───────────────────────────────────────────────────────────

    /**
     * Recursively upsert a nested category tree, preserving hierarchy.
     *
     * Processes parent before children — so internal parent_id is always
     * known by the time children are inserted. No two-pass needed.
     *
     * @param array    $items            Current level's category nodes
     * @param int|null $parentInternalId Internal DB id of the parent (null = root)
     * @param array    &$stats           Running [ inserted, updated, errors ]
     */
    private function importRecursive(array $items, ?int $parentInternalId, array &$stats): void
    {
        foreach ($items as $cat) {
            if (empty($cat['id']) || empty($cat['name']) || empty($cat['slug'])) {
                $stats['errors'][] = "Skipped — missing id/name/slug: "
                    . json_encode(array_intersect_key($cat, array_flip(['id', 'name', 'slug'])));
                continue;
            }

            $payload = [
                'wc_id'        => (int) $cat['id'],
                'name'         => $cat['name'],
                'slug'         => $cat['slug'],
                'description'  => $cat['description'] ?? null,
                // Pass the already-resolved internal parent id directly,
                // bypassing upsertFromWc()'s wc_parent_id → internal id lookup.
                // This is safe because we processed the parent in the call above.
                '_parent_internal_id' => $parentInternalId,
            ];

            try {
                $existing   = $this->model->where('wc_id', $payload['wc_id'])->first();
                $internalId = $this->model->upsertFromWcRecursive($payload);
                $existing ? $stats['updated']++ : $stats['inserted']++;
            } catch (\Exception $e) {
                $stats['errors'][] = "wc_id={$payload['wc_id']} ({$payload['name']}): " . $e->getMessage();
                $internalId = $existing->id ?? null; // still recurse into children if we have an id
            }

            // Recurse into children — parent now guaranteed to exist in DB
            $children = $cat['children'] ?? [];
            if (!empty($children) && $internalId) {
                $this->importRecursive($children, $internalId, $stats);
            }
        }
    }

    public function import()
    {
        return view('admin/categories/import', [
            'title' => 'Import Categories',
        ]);
    }

    public function importProcess()
    {
        // Accept either a JSON file upload or raw JSON pasted into textarea
        $json = null;

        $file = $this->request->getFile('json_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            if ($file->getClientMimeType() !== 'application/json' && $file->getClientExtension() !== 'json') {
                return redirect()->to(self::BASE . '/import')->with('error', 'File must be a .json file.');
            }
            $json = file_get_contents($file->getTempName());
        } else {
            $json = $this->request->getPost('json_raw');
        }

        if (empty($json)) {
            return redirect()->to(self::BASE . '/import')->with('error', 'No JSON provided.');
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()->to(self::BASE . '/import')
                             ->with('error', 'Invalid JSON: ' . json_last_error_msg());
        }

        // Support:
        //  A) Full API envelope with nested tree  { "categories": [ { ...,"children": [...] } ] }
        //  B) Full API envelope flat              { "categories": [ {...}, {...} ] }
        //  C) Bare array                          [ {...}, {...} ]
        $raw = $data['categories'] ?? (isset($data[0]) ? $data : null);

        if (empty($raw) || !is_array($raw)) {
            return redirect()->to(self::BASE . '/import')
                             ->with('error', 'No categories array found in the JSON.');
        }

        // ------------------------------------------------------------------
        // Recursive import — processes each node before its children,
        // so the internal parent_id is always known when children are inserted.
        // Hierarchy is preserved exactly as it appears in the JSON.
        // product_count from JSON is intentionally ignored — the column
        // is kept for updates triggered by product sync, not bulk import.
        // ------------------------------------------------------------------
        $stats = ['inserted' => 0, 'updated' => 0, 'errors' => []];
        $this->importRecursive($raw, null, $stats);

        $inserted = $stats['inserted'];
        $updated  = $stats['updated'];
        $errors   = $stats['errors'];

        $summary = "Done — {$inserted} inserted, {$updated} updated.";
        if (!empty($errors)) {
            $summary .= ' ' . count($errors) . ' error(s) — check logs.';
            foreach ($errors as $err) {
                log_message('error', '[CategoryImport] ' . $err);
            }
        }

        return redirect()->to(self::BASE)->with('success', $summary);
    }

    // ── Delete ───────────────────────────────────────────────────────────

    public function delete(int $id)
    {
        $category = $this->model->find($id);

        if (!$category) {
            return redirect()->to(self::BASE)->with('error', 'Category not found.');
        }

        try {
            $this->model->delete($id);
            return redirect()->to(self::BASE)->with('success', 'Category deleted.');
        } catch (\Exception $e) {
            return redirect()->to(self::BASE . '/' . $id)
                             ->with('error', 'Cannot delete: category has sub-categories. Delete or re-parent them first.');
        }
    }
}