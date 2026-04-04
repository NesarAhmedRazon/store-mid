<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table      = 'product_categories';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'wc_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'path',
        'depth',
        'thumb_id',
        'sort_order',
    ];

    protected $useTimestamps = true;

    // ── Write helpers ────────────────────────────────────────────────────

    /**
     * Insert a category and automatically compute path + depth from parent.
     * Returns the new category ID.
     */
    public function insertWithPath(array $data): int
    {
        if (!empty($data['parent_id'])) {
            $parent = $this->find($data['parent_id']);
            $data['depth'] = $parent ? $parent->depth + 1 : 0;
            // path will be set after we know our own ID
        } else {
            $data['parent_id'] = null;
            $data['depth']     = 0;
        }

        $id = $this->insert($data, true);

        // Build path now that we have our own ID
        if (!empty($data['parent_id']) && isset($parent)) {
            $path = $parent->path . '/' . $id;
        } else {
            $path = (string) $id;
        }

        $this->update($id, ['path' => $path]);

        return $id;
    }

    /**
     * Move a category to a new parent and rebuild paths for it and all descendants.
     */
    public function moveTo(int $id, ?int $newParentId): bool
    {
        $category = $this->find($id);
        if (!$category) {
            return false;
        }

        $oldPath = $category->path;

        if ($newParentId) {
            $parent  = $this->find($newParentId);
            $newPath = $parent->path . '/' . $id;
            $newDepth = $parent->depth + 1;
        } else {
            $newPath  = (string) $id;
            $newDepth = 0;
        }

        // Update the moved category itself
        $this->update($id, [
            'parent_id' => $newParentId,
            'path'      => $newPath,
            'depth'     => $newDepth,
        ]);

        // Rebuild paths for all descendants in one pass
        $descendants = $this->getDescendants($id);

        foreach ($descendants as $desc) {
            $updatedPath  = $newPath . substr($desc->path, strlen($oldPath));
            $updatedDepth = substr_count($updatedPath, '/');

            $this->update($desc->id, [
                'path'  => $updatedPath,
                'depth' => $updatedDepth,
            ]);
        }

        return true;
    }

    // ── Hierarchy queries ────────────────────────────────────────────────

    /**
     * All descendants of a category (any depth).
     * Uses path prefix scan — no recursion needed.
     */
    public function getDescendants(int $id): array
    {
        $category = $this->find($id);
        if (!$category) {
            return [];
        }

        return $this->where('path !=', $category->path)
                    ->like('path', $category->path . '/', 'after')  // prefix: "path/"
                    ->orderBy('path', 'ASC')
                    ->findAll();
    }

    /**
     * Direct children only.
     */
    public function getChildren(int $parentId): array
    {
        return $this->where('parent_id', $parentId)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    /**
     * All ancestors of a category, ordered root → parent.
     * Parses the path string — no recursive query.
     */
    public function getAncestors(int $id): array
    {
        $category = $this->find($id);
        if (!$category || $category->depth === 0) {
            return [];
        }

        $ancestorIds = array_filter(
            explode('/', $category->path),
            fn($pid) => (int) $pid !== $id
        );

        if (empty($ancestorIds)) {
            return [];
        }

        // Preserve root → parent order using FIELD()
        $ids = implode(',', array_map('intval', $ancestorIds));

        return $this->db
                    ->table($this->table)
                    ->whereIn('id', $ancestorIds)
                    ->orderBy("FIELD(id, {$ids})")
                    ->get()
                    ->getResult();
    }

    /**
     * Full breadcrumb array for a category, root first.
     * [ {id, name, slug}, ... , category itself ]
     */
    public function getBreadcrumb(int $id): array
    {
        $category  = $this->find($id);
        if (!$category) {
            return [];
        }

        $ancestors = $this->getAncestors($id);

        return [...$ancestors, $category];
    }

    /**
     * Entire tree as a flat list ordered by path (natural hierarchy order).
     */
    public function getTree(): array
    {
        return $this->orderBy('path', 'ASC')->findAll();
    }

    /**
     * All root categories (depth = 0).
     */
    public function getRoots(): array
    {
        return $this->where('depth', 0)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    // ── Product linkage ──────────────────────────────────────────────────

    /**
     * All categories a product belongs to, primary first.
     */
    public function getByProduct(int $productId): array
    {
        return $this->db->table('product_category_map pcm')
                        ->select('pc.*, pcm.is_primary')
                        ->join('product_categories pc', 'pc.id = pcm.category_id')
                        ->where('pcm.product_id', $productId)
                        ->orderBy('pcm.is_primary', 'DESC')
                        ->orderBy('pc.path', 'ASC')
                        ->get()
                        ->getResult();
    }

    /**
     * All products in a category AND its descendants (subtree).
     * Useful for browsing — "show all Electronics" includes sub-categories.
     */
    public function getProductIds(int $categoryId, bool $includeDescendants = true): array
    {
        if ($includeDescendants) {
            $category = $this->find($categoryId);
            if (!$category) {
                return [];
            }

            // Collect this category + all descendant IDs
            $descendants = $this->getDescendants($categoryId);
            $catIds      = array_merge(
                [$categoryId],
                array_column($descendants, 'id')
            );
        } else {
            $catIds = [$categoryId];
        }

        $rows = $this->db->table('product_category_map')
                         ->select('product_id')
                         ->whereIn('category_id', $catIds)
                         ->distinct()
                         ->get()
                         ->getResultArray();

        return array_column($rows, 'product_id');
    }

    // ── Upsert from WooCommerce ──────────────────────────────────────────

    /**
     * Upsert a category from a WC webhook payload.
     * Handles parent resolution and path computation automatically.
     *
     * Expected $data keys:
     *   wc_id, name, slug, description?, wc_parent_id?
     */
    public function upsertFromWc(array $data): int
    {
        $existing = $this->where('wc_id', (int) $data['wc_id'])->first();

        // Resolve parent by wc_id if provided
        $parentId = null;
        if (!empty($data['wc_parent_id']) && (int) $data['wc_parent_id'] !== 0) {
            $parent = $this->where('wc_id', (int) $data['wc_parent_id'])->first();
            if ($parent) {
                $parentId = $parent->id;
            }
        }

        $payload = [
            'wc_id'       => (int) $data['wc_id'],
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?? null,
            'parent_id'   => $parentId,
        ];

        if ($existing) {
            // If parent changed, moveTo() will rebuild paths for the subtree
            if ($existing->parent_id !== $parentId) {
                $this->moveTo($existing->id, $parentId);
            }

            $this->update($existing->id, $payload);

            return $existing->id;
        }

        return $this->insertWithPath($payload);
    }
}
