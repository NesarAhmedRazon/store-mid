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

    // ── Private helpers ──────────────────────────────────────────────────

    /**
     * Build a normalised upsert payload from a WooCommerce data array.
     *
     * @param array    $data     Raw WC category data.
     * @param int|null $parentId Resolved internal parent id (or null for root).
     * @return array
     */
    private function buildPayload(array $data, ?int $parentId): array
    {
        return [
            'wc_id'       => (int) $data['wc_id'],
            'name'        => html_entity_decode($data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'slug'        => $data['slug'],
            'description' => !empty($data['description'])
                ? html_entity_decode($data['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
                : null,
            'parent_id'   => $parentId,
        ];
    }

    /**
     * Shared upsert logic used by both upsertFromWc() and upsertFromWcRecursive().
     *
     * @param array    $payload  Already-built payload from buildPayload().
     * @param int|null $parentId Resolved internal parent id.
     * @return int Internal id of the upserted category.
     */
    private function upsert(array $payload, ?int $parentId): int
    {
        $existing = $this->where('wc_id', $payload['wc_id'])->first();

        if ($existing) {
            // Normalise both sides to int for a safe comparison (DB returns strings).
            if ((int) ($existing->parent_id ?? 0) !== (int) ($parentId ?? 0)) {
                $this->moveTo($existing->id, $parentId);
            }

            $this->update($existing->id, $payload);

            return $existing->id;
        }

        return $this->insertWithPath($payload);
    }

    // ── Write helpers ────────────────────────────────────────────────────

    /**
     * Insert a new category and compute its path/depth automatically.
     *
     * @param array $data Category data; may include 'parent_id'.
     * @return int Inserted row id.
     */
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

    /**
     * Move a category (and its whole subtree) to a new parent.
     *
     * Returns false if the category is not found, or if $newParentId is a
     * descendant of $id (which would create a circular reference).
     *
     * @param int      $id          Category to move.
     * @param int|null $newParentId New parent id, or null to make it a root.
     * @return bool
     */
    public function moveTo(int $id, ?int $newParentId): bool
    {
        $category = $this->find($id);
        if (!$category) {
            return false;
        }

        // Guard: prevent moving a category into its own subtree.
        if ($newParentId !== null) {
            $descendantIds = array_column($this->getDescendants($id), 'id');
            if (in_array($newParentId, $descendantIds, true)) {
                return false;
            }
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
     * Upsert a category from a WooCommerce flat sync payload.
     *
     * Resolves the parent by wc_parent_id lookup internally.
     *
     * @param array $data Raw WC category data including 'wc_parent_id'.
     * @return int Internal id of the upserted category.
     */
    public function upsertFromWc(array $data): int
    {
        $parentId = null;
        if (!empty($data['wc_parent_id']) && (int) $data['wc_parent_id'] !== 0) {
            $parent = $this->where('wc_id', (int) $data['wc_parent_id'])->first();
            if ($parent) {
                $parentId = $parent->id;
            }
        }

        return $this->upsert($this->buildPayload($data, $parentId), $parentId);
    }

    /**
     * Upsert from a recursive import payload.
     *
     * Accepts '_parent_internal_id' (already-resolved internal DB id) instead
     * of 'wc_parent_id', avoiding a redundant lookup since the controller
     * already resolved it from the previous recursion level.
     *
     * @param array $data Raw WC category data including '_parent_internal_id'.
     * @return int Internal id of the upserted category.
     */
    public function upsertFromWcRecursive(array $data): int
    {
        $parentId = isset($data['_parent_internal_id'])
            ? (int) $data['_parent_internal_id']
            : null;

        return $this->upsert($this->buildPayload($data, $parentId), $parentId);
    }

    // ── Hierarchy queries ────────────────────────────────────────────────

    /**
     * Return all descendants of a category ordered by path (breadth-first).
     *
     * Note: paths are integer-segment strings (e.g. "2/15/47"), so the LIKE
     * pattern "path/%" is unambiguous — "2/%" cannot match "20/..." because
     * the trailing slash is part of the pattern.
     *
     * @param int $id
     * @return object[]
     */
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

    /**
     * Return direct children of a category.
     *
     * @param int $parentId
     * @return object[]
     */
    public function getChildren(int $parentId): array
    {
        return $this->where('parent_id', $parentId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * Return all ancestors of a category, ordered from root to direct parent.
     *
     * Accepts an already-fetched $category object to avoid a redundant DB hit
     * when called from getBreadcrumb().
     *
     * @param int         $id
     * @param object|null $category Pre-fetched category object (optional).
     * @return object[]
     */
    public function getAncestors(int $id, ?object $category = null): array
    {
        $category ??= $this->find($id);

        // Cast depth to int — the DB driver returns it as a string.
        if (!$category || (int) $category->depth === 0) {
            return [];
        }

        $ancestorIds = array_values(array_filter(
            explode('/', $category->path),
            fn($pid) => (int) $pid !== $id
        ));

        if (empty($ancestorIds)) {
            return [];
        }

        // Fetch all ancestors in one query, then reorder in PHP.
        // Ancestors are always a small set (max tree depth), so this is
        // cheaper than injecting a raw FIELD() expression into SQL.
        $results = $this->db
            ->table($this->table)
            ->whereIn('id', $ancestorIds)
            ->get()
            ->getResult();

        // Index by id for O(1) lookup, then rebuild in path order.
        $indexed = [];
        foreach ($results as $row) {
            $indexed[(int) $row->id] = $row;
        }

        return array_values(array_filter(
            array_map(fn($pid) => $indexed[(int) $pid] ?? null, $ancestorIds)
        ));
    }

    /**
     * Return an ordered breadcrumb array for a category, shaped for API output.
     *
     * Each entry contains only 'id', 'name', and a cumulative 'permalink'
     * built by joining slugs from root to the current category.
     *
     * Example output:
     * [
     *   ['id' => 2,  'name' => 'Power Management',  'permalink' => 'power-management'],
     *   ['id' => 15, 'name' => 'DC-DC Converters',  'permalink' => 'power-management/dc-dc-converters'],
     * ]
     *
     * @param int $id
     * @return array<int, array{id: int, name: string, permalink: string}>
     */
    public function getBreadcrumb(int $id): array
    {
        // Fetch once and pass into getAncestors() to avoid a double DB hit.
        $category = $this->find($id);
        if (!$category) {
            return [];
        }

        $crumbs = [...$this->getAncestors($id, $category), $category];

        $permalink = '';
        return array_map(function ($cat) use (&$permalink) {
            $permalink = $permalink
                ? "{$permalink}/{$cat->slug}"
                : $cat->slug;

            return [
                'id'        => (int) $cat->id,
                'name'      => $cat->name,
                'url' => $permalink,
            ];
        }, $crumbs);
    }

    /**
     * Return the full category tree ordered by path (root nodes first).
     *
     * @return object[]
     */
    public function getTree(): array
    {
        return $this->orderBy('path', 'ASC')->findAll();
    }

    /**
     * Return all root-level categories (depth 0).
     *
     * @return object[]
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
     * Return all categories linked to a product, primary category first.
     *
     * @param int $productId
     * @return object[]
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
     * Return product ids assigned to a category, optionally including all
     * descendants.
     *
     * Selects only 'id' columns when fetching descendants to avoid
     * over-fetching full category objects.
     *
     * @param int  $categoryId
     * @param bool $includeDescendants Include products from subcategories.
     * @return int[]
     */
    public function getProductIds(int $categoryId, bool $includeDescendants = true): array
    {
        if ($includeDescendants) {
            $category = $this->find($categoryId);
            if (!$category) {
                return [];
            }

            // Select only ids — no need to hydrate full category objects.
            $descRows = $this->db->table($this->table)
                ->select('id')
                ->like('path', $category->path . '/', 'after')
                ->where('path !=', $category->path)
                ->get()
                ->getResultArray();

            $catIds = array_merge([$categoryId], array_column($descRows, 'id'));
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
     *
     * Returns [ category_id => count ] for O(1) lookup.
     * Direct assignments only (not subtree) — use getProductIds() when you
     * need the full descendant count.
     *
     * @return array<int, int>
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
}