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
        'product_count',
        'path',
        'depth',
        'thumb_id',
        'sort_order',
    ];

    protected $useTimestamps = true;

    // ── Write helpers ────────────────────────────────────────────────────

    public function insertWithPath(array $data): int
    {
        if (!empty($data['parent_id'])) {
            $parent        = $this->find($data['parent_id']);
            $data['depth'] = $parent ? $parent->depth + 1 : 0;
        } else {
            $data['parent_id'] = null;
            $data['depth']     = 0;
        }

        $id = $this->insert($data, true);

        $path = (!empty($data['parent_id']) && isset($parent))
            ? $parent->path . '/' . $id
            : (string) $id;

        $this->update($id, ['path' => $path]);

        return $id;
    }

    public function moveTo(int $id, ?int $newParentId): bool
    {
        $category = $this->find($id);
        if (!$category) {
            return false;
        }

        $oldPath = $category->path;

        if ($newParentId) {
            $parent   = $this->find($newParentId);
            $newPath  = $parent->path . '/' . $id;
            $newDepth = $parent->depth + 1;
        } else {
            $newPath  = (string) $id;
            $newDepth = 0;
        }

        $this->update($id, [
            'parent_id' => $newParentId,
            'path'      => $newPath,
            'depth'     => $newDepth,
        ]);

        foreach ($this->getDescendants($id) as $desc) {
            $updatedPath = $newPath . substr($desc->path, strlen($oldPath));
            $this->update($desc->id, [
                'path'  => $updatedPath,
                'depth' => substr_count($updatedPath, '/'),
            ]);
        }

        return true;
    }

    /**
     * Upsert from a recursive import payload.
     *
     * Differs from upsertFromWc() in one way: accepts '_parent_internal_id'
     * (the already-resolved internal DB id) instead of 'wc_parent_id'.
     * This avoids a redundant lookup since the controller already knows the
     * internal id from the previous recursion level.
     *
     * Returns the internal id of the upserted category.
     */
    public function upsertFromWcRecursive(array $data): int
    {
        $existing = $this->where('wc_id', (int) $data['wc_id'])->first();

        $parentInternalId = $data['_parent_internal_id'] ?? null;

        $payload = [
            'wc_id'       => (int) $data['wc_id'],
            'name'        => html_entity_decode($data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'slug'        => $data['slug'],
            'description' => !empty($data['description'])
                ? html_entity_decode($data['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
                : null,
            'parent_id'   => $parentInternalId,
        ];

        if ($existing) {
            // Repair hierarchy if parent changed
            if ((int) ($existing->parent_id ?? 0) !== (int) ($parentInternalId ?? 0)) {
                $this->moveTo($existing->id, $parentInternalId);
            }
            $this->update($existing->id, $payload);
            return $existing->id;
        }

        return $this->insertWithPath($payload);
    }

    // ── Hierarchy queries ────────────────────────────────────────────────

    public function getDescendants(int $id): array
    {
        $category = $this->find($id);
        if (!$category) {
            return [];
        }

        return $this->where('path !=', $category->path)
            ->like('path', $category->path . '/', 'after')
            ->orderBy('path', 'ASC')
            ->findAll();
    }

    public function getChildren(int $parentId): array
    {
        return $this->where('parent_id', $parentId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

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

        $ids = implode(',', array_map('intval', $ancestorIds));

        return $this->db
            ->table($this->table)
            ->whereIn('id', $ancestorIds)
            ->orderBy("FIELD(id, {$ids})")
            ->get()
            ->getResult();
    }

    public function getBreadcrumb(int $id): array
    {
        $category = $this->find($id);
        if (!$category) {
            return [];
        }

        return [...$this->getAncestors($id), $category];
    }

    public function getTree(): array
    {
        return $this->orderBy('path', 'ASC')->findAll();
    }

    public function getRoots(): array
    {
        return $this->where('depth', 0)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    // ── Product linkage ──────────────────────────────────────────────────

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

    public function getProductIds(int $categoryId, bool $includeDescendants = true): array
    {
        if ($includeDescendants) {
            $category = $this->find($categoryId);
            if (!$category) {
                return [];
            }

            $catIds = array_merge(
                [$categoryId],
                array_column($this->getDescendants($categoryId), 'id')
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

    /**
     * Bulk product counts for ALL categories in one query.
     * Returns [ category_id => count ] for O(1) lookup.
     *
     * Direct assignments only (not subtree) — use getProductIds()
     * when you need the full descendant count.
     */
    public function getProductCountsAll(): array
    {
        $rows = $this->db->table('product_category_map')
            ->select('category_id, COUNT(DISTINCT product_id) as count')
            ->groupBy('category_id')
            ->get()
            ->getResultArray();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['category_id']] = (int) $row['count'];
        }

        return $counts;
    }

    // ── Upsert from WooCommerce ──────────────────────────────────────────

    public function upsertFromWc(array $data): int
    {
        $existing = $this->where('wc_id', (int) $data['wc_id'])->first();

        $parentId = null;
        if (!empty($data['wc_parent_id']) && (int) $data['wc_parent_id'] !== 0) {
            $parent = $this->where('wc_id', (int) $data['wc_parent_id'])->first();
            if ($parent) {
                $parentId = $parent->id;
            }
        }

        $payload = [
            'wc_id'         => (int) $data['wc_id'],
            'name'          => html_entity_decode($data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'slug'          => $data['slug'],
            'description'   => !empty($data['description'])
                ? html_entity_decode($data['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
                : null,
            'product_count' => (int) ($data['product_count'] ?? 0),
            'parent_id'     => $parentId,
        ];

        if ($existing) {
            if ($existing->parent_id !== $parentId) {
                $this->moveTo($existing->id, $parentId);
            }

            $this->update($existing->id, $payload);

            return $existing->id;
        }

        return $this->insertWithPath($payload);
    }
}
