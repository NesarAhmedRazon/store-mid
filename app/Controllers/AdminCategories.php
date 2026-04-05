<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\ProductModel;

class AdminCategories extends BaseController
{
    protected CategoryModel $model;

    public function __construct()
    {
        $this->model = new CategoryModel();
    }

    // ── Index ────────────────────────────────────────────────────────────

    public function index()
    {
        // getTree() sorts by path — natural hierarchy order
        $categories = $this->model->getTree();

        // Single query for all product counts — no N+1
        $counts = $this->model->getProductCountsAll();

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
            return redirect()->to('/categories')->with('error', 'Category not found.');
        }

        $productModel = new ProductModel();
        $productIds   = $this->model->getProductIds($id, true);

        $products = [];
        foreach ($productIds as $productId) {
            $product = $productModel->find($productId);
            if ($product) {
                $products[] = $product;
            }
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

        return redirect()->to('/categories')->with('success', 'Category created.');
    }

    // ── Edit ─────────────────────────────────────────────────────────────

    public function edit(int $id)
    {
        $category = $this->model->find($id);

        if (!$category) {
            return redirect()->to('/categories')->with('error', 'Category not found.');
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
            return redirect()->to('/categories')->with('error', 'Category not found.');
        }

        $rules = [
            'name' => 'required|min_length[2]|max_length[120]',
            'slug' => "required|min_length[2]|max_length[140]|is_unique[product_categories.slug,id,{$id}]",
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

        return redirect()->to('/categories/' . $id)->with('success', 'Category updated.');
    }

    // ── Delete ───────────────────────────────────────────────────────────

    public function delete(int $id)
    {
        $category = $this->model->find($id);

        if (!$category) {
            return redirect()->to('/categories')->with('error', 'Category not found.');
        }

        // FK on parent_id is RESTRICT — deleting a parent with children
        // will throw. Catch it and return a friendly message.
        try {
            $this->model->delete($id);
            return redirect()->to('/categories')->with('success', 'Category deleted.');
        } catch (\Exception $e) {
            return redirect()->to('/categories/' . $id)
                             ->with('error', 'Cannot delete: category has sub-categories. Delete or re-parent them first.');
        }
    }
}