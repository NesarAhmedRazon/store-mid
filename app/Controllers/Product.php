<?php
// app/Controllers/Product.php
// Handles incoming POST requests from WooCommerce on $routes->post('posts/product', 'Product::receive');.
// Upserts product, attributes, values, pivot map, and media in a single transaction.

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProductModel;
use CodeIgniter\I18n\Time;
use App\Models\CategoryModel;
use App\Models\MetaModel;

class Product extends ResourceController
{
    public function receive()
    {
        // ── Auth ────────────────────────────────────────────────────────
        $secret = $this->request->getHeaderLine('X-WC-Webhook-Secret');

        if ($secret !== env('WC_WEBHOOK_SECRET')) {
            log_message('error', 'Invalid webhook secret provided');
            return $this->failUnauthorized('Invalid webhook secret');
        }

        // ── Parse payload ───────────────────────────────────────────────
        $data = $this->request->getJSON(true);


        if (!$data || empty($data['wc_id']) || empty($data['title']) || empty($data['permalink'])) {
            log_message('error', 'Invalid or empty JSON payload');
            return $this->fail('Missing required fields', 422);
        }


        $db    = \Config\Database::connect();
        $model = new ProductModel();

        try {
            $db->transStart();
            log_message('debug', $data['permalink']);
            // ── Upsert product ───────────────────────────────────────────
            $productData = [
                'wc_id'          => $data['wc_id'],
                'permalink'      => basename(trim($data['permalink'], '/')),
                'title'          => $data['title'],
                'sku'            => $data['sku'] ?? null,
                'stock_quantity' => $data['stock']['quantity'] ?? null,
                'stock_unit'     => $data['stock']['unit'] ?? null,
                'stock_status'   => $data['stock']['status'] ?? 'outofstock',
                'sale_price'     => $data['sale_price'] ?? null,
                'regular_price'  => $data['regular_price'] ?? 0,
                'wc_created_at'  => $data['created_at'] ?? Time::now()->toDateTimeString(),
                'cost'           => $data['wc_cog'] ?? 0,
            ];

            $existing = $model->where('wc_id', $data['wc_id'])->first();

            if ($existing) {
                $model->update($existing->id, $productData);
                $productId = $existing->id;
            } else {
                $productId = $model->insert($productData, true);
            }

            // ── Media: thumbnail ─────────────────────────────────────────
            // Remove old media_entities rows for this product so they're re-linked fresh.
            // The media rows themselves are kept (shared across entities).
            $db->table('media_entities')
                ->where('entity_type', 'product')
                ->where('entity_id', $productId)
                ->delete();

            $thumbMediaId = null;

            if (!empty($data['thumbnail'])) {
                $thumbMediaId = $this->upsertUrlMedia($db, $data['thumbnail']);

                $db->table('media_entities')->insert([
                    'media_id'    => $thumbMediaId,
                    'entity_type' => 'product',
                    'entity_id'   => $productId,
                    'role'        => 'thumbnail',
                    'sort_order'  => 0,
                ]);
            }

            // Keep products.thumb_id in sync for direct FK access (no join needed)
            $model->update($productId, ['thumb_id' => $thumbMediaId]);

            // ── Media: gallery ───────────────────────────────────────────
            if (!empty($data['gallery']) && is_array($data['gallery'])) {
                foreach ($data['gallery'] as $order => $url) {
                    if (empty($url)) {
                        continue;
                    }

                    $mediaId = $this->upsertUrlMedia($db, $url);

                    $db->table('media_entities')->insert([
                        'media_id'    => $mediaId,
                        'entity_type' => 'product',
                        'entity_id'   => $productId,
                        'role'        => 'gallery',
                        'sort_order'  => (int) $order,
                    ]);
                }
            }

            // ── Upsert attributes ────────────────────────────────────────
            if (!empty($data['attributes']) && is_array($data['attributes'])) {

                // Clear existing pivot rows — will be re-linked fresh below
                $db->table('product_attribute_map')
                    ->where('product_id', $productId)
                    ->delete();

                foreach ($data['attributes'] as $attr) {

                    if (empty($attr['wc_id']) || empty($attr['name']) || empty($attr['values'])) {
                        continue;
                    }

                    // ── Upsert attribute row ─────────────────────────────
                    $attrRow = $db->table('product_attributes')
                        ->where('wc_id', (int) $attr['wc_id'])
                        ->get()->getRowArray();

                    if ($attrRow) {
                        $db->table('product_attributes')
                            ->where('wc_id', (int) $attr['wc_id'])
                            ->update([
                                'label'     => $attr['label']     ?? $attrRow['label'],
                                'is_public' => (int) ($attr['is_public'] ?? $attrRow['is_public']),
                            ]);
                        $attributeId = $attrRow['id'];
                    } else {
                        $db->table('product_attributes')->insert([
                            'wc_id'     => (int) $attr['wc_id'],
                            'name'      => $attr['name'],
                            'label'     => $attr['label'] ?? $attr['name'],
                            'is_public' => (int) ($attr['is_public'] ?? 0),
                        ]);
                        $attributeId = $db->insertID();
                    }

                    // ── Upsert each value + link to product ──────────────
                    foreach ($attr['values'] as $val) {

                        if (empty($val['wc_id']) || empty($val['name'])) {
                            continue;
                        }

                        $valRow = $db->table('product_attribute_values')
                            ->where('wc_id', (int) $val['wc_id'])
                            ->get()->getRowArray();

                        if ($valRow) {
                            $db->table('product_attribute_values')
                                ->where('wc_id', (int) $val['wc_id'])
                                ->update([
                                    'name' => $val['name'],
                                    'slug' => $val['slug'] ?? $valRow['slug'],
                                ]);
                            $valueId = $valRow['id'];
                        } else {
                            $db->table('product_attribute_values')->insert([
                                'wc_id'        => (int) $val['wc_id'],
                                'attribute_id' => $attributeId,
                                'slug'         => $val['slug'] ?? '',
                                'name'         => $val['name'],
                            ]);
                            $valueId = $db->insertID();
                        }

                        // ── Insert pivot row ─────────────────────────────
                        $db->table('product_attribute_map')->insert([
                            'product_id'   => $productId,
                            'attribute_id' => $attributeId,
                            'value_id'     => $valueId,
                            'order'        => $attr['order'] ?? null,
                        ]);
                    }
                }
            }

            if (!empty($data['categories']) && is_array($data['categories'])) {
                $this->handleCategories($db, $productId, $data['categories']);
            }

            // ── Upsert product metas ─────────────────────────────────────
            // Expects $data['metas'] as an array of { slug, label, value } objects.
            // Uses replace=true so stale keys from a previous sync are always removed.

            // Define your allowed meta keys
            $allowedMetaKeys = [
                'trd_price',
                'rating',
                'total_sales',
                'extra_documents',
                'sku',
                'stock_status',
                'stock',
                'seo',
                'smdp_moq',
                'cogs_total_value'
                // Add other meta keys you want to process
            ];

            if (!empty($data['metas']) && is_array($data['metas'])) {
                // Filter only the allowed meta keys
                $filteredMeta = array_intersect_key($data['metas'], array_flip($allowedMetaKeys));

                // Only sync if there are allowed meta keys
                if (!empty($filteredMeta)) {
                    (new MetaModel())->syncFromPayload(
                        MetaModel::ENTITY_PRODUCT,
                        $productId,
                        $filteredMeta,
                        replace: true
                    );
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                log_message('error', 'Transaction failed for wc_id: ' . $data['wc_id']);
                return $this->fail('Transaction failed', 500);
            }
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Product receive exception: ' . $e->getMessage());
            return $this->fail('Server error: ' . $e->getMessage(), 500);
        }

        return $this->respond([
            'status'     => 'ok',
            'wc_id'      => $data['wc_id'],
            'product_id' => $productId,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Upsert a media row for an external URL.
     * Deduplicates by path so the same WC image URL is never stored twice.
     *
     * Returns the media.id.
     */
    private function upsertUrlMedia(\CodeIgniter\Database\BaseConnection $db, string $url): int
    {
        $existing = $db->table('media')
            ->where('disk', 'url')
            ->where('path', $url)
            ->get()->getRowArray();

        if ($existing) {
            return (int) $existing['id'];
        }

        $db->table('media')->insert([
            'disk'      => 'url',
            'path'      => $url,
            'file_name' => basename(parse_url($url, PHP_URL_PATH)) ?: null,
            'mime_type' => $this->guessMimeFromUrl($url),
        ]);

        return (int) $db->insertID();
    }

    /**
     * Best-effort MIME guess from file extension in a URL.
     * No HTTP request made — disk=url rows are URL-only.
     */
    private function guessMimeFromUrl(string $url): ?string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'svg'         => 'image/svg+xml',
            default       => null,
        };
    }

    // ----- Category Helper -----
    // ── Helpers ──────────────────────────────────────────────────────────

    private function handleCategories(\CodeIgniter\Database\BaseConnection $db, int $productId, array $categories): void
    {
        if (empty($categories)) {
            return;
        }

        $db->table('product_category_map')->where('product_id', $productId)->delete();

        $categoryModel = new CategoryModel();

        foreach ($categories as $index => $cat) {

            if (empty($cat['wc_id']) || empty($cat['name']) || empty($cat['slug'])) {
                continue;
            }

            $categoryId = $categoryModel->upsertFromWc([
                'wc_id'        => $cat['wc_id'],
                'name'         => $cat['name'],
                'slug'         => $cat['slug'],
                'description'  => $cat['description'] ?? null,
                'wc_parent_id' => $cat['parent']      ?? 0,
            ]);

            $db->table('product_category_map')->insert([
                'product_id'  => $productId,
                'category_id' => $categoryId,
                'is_primary'  => $index === 0 ? 1 : 0,
            ]);
        }
    }
}