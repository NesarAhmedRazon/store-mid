<?php

namespace App\Models;

use CodeIgniter\Model;

class MediaModel extends Model
{
    protected $table      = 'media';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'disk',
        'path',
        'file_name',
        'mime_type',
        'size',
        'width',
        'height',
        'alt',
        'title',
    ];

    protected $useTimestamps = true;

    // ── URL helpers ──────────────────────────────────────────────────────

    /**
     * Resolve the public-facing URL for any media record.
     *
     * disk=url       → path is already a full URL, return as-is
     * disk=local     → base_url() + path  (e.g. base_url('media/2026/03/image.jpg'))
     * disk=cloudinary → TODO: build Cloudinary delivery URL from path (public ID)
     */
    public function resolveUrl(object $media): string
    {
        return match ($media->disk) {
            'url'        => $media->path,
            'local'      => base_url($media->path),
            'cloudinary' => $media->path, // TODO: replace with Cloudinary SDK URL builder
            default      => $media->path,
        };
    }

    // ── Entity lookups ───────────────────────────────────────────────────

    /**
     * Get all media for a single entity, grouped by role.
     *
     * Returns:
     * [
     *   'thumbnail'  => media object | null,
     *   'gallery'    => [media, media, ...],
     *   'attachment' => [media, ...],
     * ]
     */
    public function getForEntity(string $entityType, int $entityId): array
    {
        $rows = $this->db->table('media_entities me')
            ->select('m.*, me.role, me.sort_order')
            ->join('media m', 'm.id = me.media_id')
            ->where('me.entity_type', $entityType)
            ->where('me.entity_id', $entityId)
            ->orderBy('me.sort_order', 'ASC')
            ->get()
            ->getResult();

        return $this->groupByRole($rows);
    }

    /**
     * Bulk fetch media for multiple entities of the same type.
     * One query regardless of how many entity IDs are passed — critical for lists.
     *
     * Returns:
     * [
     *   entity_id => [
     *     'thumbnail'  => media object | null,
     *     'gallery'    => [...],
     *     'attachment' => [...],
     *   ],
     *   ...
     * ]
     */
/**
 * Bulk fetch media with optional role filtering.
 */
public function getForEntities(string $entityType, array $entityIds, array $roles = []): array
{
    if (empty($entityIds)) {
        return [];
    }

    $builder = $this->db->table('media_entities me')
        ->select('m.*, me.entity_id, me.role, me.sort_order')
        ->join('media m', 'm.id = me.media_id')
        ->where('me.entity_type', $entityType)
        ->whereIn('me.entity_id', $entityIds);

    // --- Optimization: Filter by role if provided ---
    if (!empty($roles)) {
        $builder->whereIn('me.role', $roles);
    }

    $rows = $builder->orderBy('me.sort_order', 'ASC')
        ->get()
        ->getResult();

    $result = [];
    foreach ($rows as $row) {
        $eid = $row->entity_id;
        $result[$eid] ??= ['thumbnail' => null, 'gallery' => [], 'attachment' => []];

        if ($row->role === 'thumbnail') {
            $result[$eid]['thumbnail'] = $row;
        } else {
            $result[$eid][$row->role][] = $row;
        }
    }

    return $result;
}

    /**
     * Convenience: get just the thumbnail for one entity.
     */
    public function getThumbnail(string $entityType, int $entityId): ?object
    {
        $row = $this->db->table('media_entities me')
            ->select('m.*, me.role, me.sort_order')
            ->join('media m', 'm.id = me.media_id')
            ->where('me.entity_type', $entityType)
            ->where('me.entity_id', $entityId)
            ->where('me.role', 'thumbnail')
            ->get()
            ->getRow();

        return $row ?: null;
    }

    // ── Private ──────────────────────────────────────────────────────────

    private function groupByRole(array $rows): array
    {
        $grouped = [
            'thumbnail'  => null,
            'gallery'    => [],
            'attachment' => [],
        ];

        foreach ($rows as $row) {
            if ($row->role === 'thumbnail') {
                $grouped['thumbnail'] = $row;
            } else {
                $grouped[$row->role][] = $row;
            }
        }

        return $grouped;
    }

 /**
 * In App\Models\MediaModel
 */
public function getFlatImages(array $media, string $mode): array
{
    $list = [];

    // 1. Add Thumbnail
    if (!empty($media['thumbnail'])) {
        $list[] = $this->formatImage($media['thumbnail']);
    }

    // 2. Add Gallery (if full mode)
    if ($mode === 'full' && !empty($media['gallery'])) {
        foreach ($media['gallery'] as $img) {
            $list[] = $this->formatImage($img);
        }
    }

    // 3. Sort by DB sort_order first
    usort($list, function ($a, $b) {
        return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
    });

    // 4. Force Sequential Indexing (0, 1, 2, 3...)
    foreach ($list as $index => &$item) {
        $item['sort_order'] = $index;
    }
    unset($item);

    return $list;
}

/**
 * Format a single media object into the frontend shape
 */
    private function formatImage(object $media): array
    {
        $args = [
            'src' => $this->resolveUrl($media),
            'sort_order' => (int) ($media->sort_order ?? 0),
        ];
        $media->alt && $args['alt'] = $media->alt;
        $media->title && $args['title'] = $media->title;
        $media->width && $args['width'] = (int) $media->width;
        $media->height && $args['height'] = (int) $media->height;
        return $args;
    }
}
