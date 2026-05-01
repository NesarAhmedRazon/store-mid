<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/*
 * Shared view for product create AND edit.
 *
 * Variables expected from controller:
 *   $mode        — 'create' | 'edit'
 *   $product     — product object (edit mode) | null (create mode)
 *   $meta        — [ slug => value ] map from MetaModel::getMap() | []
 *   $formAction  — URL the form posts to
 *   $errors      — validation errors array | []
 *
 * Meta keys handled:
 *   title_bn     → 'বাংলা টাইটেল'
 *   seo.title    → SEO Title
 *   seo.description → SEO Description (bonus)
 */

$isEdit     = ($mode ?? 'create') === 'edit';
$product    = $product ?? null;
$meta       = $meta    ?? [];
$errors     = $errors  ?? [];
$formAction = $formAction ?? '/products/store';

// Helpers — pull existing value or fall back to empty
$val = fn(string $field) => esc(old($field, $product?->$field ?? ''));
$metaVal = fn(string $slug) => esc(old('meta_' . str_replace('.', '_', $slug), $meta[$slug] ?? ''));

// SEO meta — stored as JSON { title, description } under slug "seo"
$seoMeta = is_array($meta['seo'] ?? null) ? $meta['seo'] : [];
$seoTitle = esc(old('meta_seo_title', $seoMeta['title'] ?? ''));
$seoDesc  = esc(old('meta_seo_description', $seoMeta['description'] ?? ''));
?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-[12px] font-mono text-subtle mb-5">
    <a href="/products" class="hover:text-muted transition-colors no-underline">Products</a>
    <?php if ($isEdit && $product): ?>
    <span>/</span>
    <a href="/products/preview?id=<?= $product->id ?>" class="hover:text-muted transition-colors no-underline truncate max-w-[200px]">
        <?= esc($product->title) ?>
    </a>
    <?php endif; ?>
    <span>/</span>
    <span class="text-text"><?= $isEdit ? 'Edit' : 'New product' ?></span>
</div>

<!-- Validation errors -->
<?php if (!empty($errors)): ?>
<div class="bg-down-bg border border-down/20 text-down text-[13px] px-4 py-3 rounded-lg mb-5 flex flex-col gap-1">
    <?php foreach ($errors as $e): ?>
    <div><?= esc($e) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?= form_open($formAction) ?>
<?php if ($isEdit && $product): ?>
<input type="hidden" name="_method" value="PUT">
<input type="hidden" name="product_id" value="<?= $product->id ?>">
<?php endif; ?>

<div class="grid grid-cols-3 gap-4">

    <!-- ── Left / main (2/3) ── -->
    <div class="col-span-2 flex flex-col gap-4">

        <!-- Title block -->
        <div class="card">
            <div class="card-head"><span class="card-title">Product title</span></div>
            <div class="p-5 flex flex-col gap-4">

                <!-- English title -->
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">
                        Title <span class="text-down">*</span>
                    </label>
                    <input
                        type="text"
                        name="title"
                        value="<?= $val('title') ?>"
                        placeholder="Product title in English"
                        class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md placeholder:text-subtle"
                        required
                    >
                </div>

                <!-- বাংলা টাইটেল -->
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">
                        বাংলা টাইটেল
                    </label>
                    <input
                        type="text"
                        name="meta_title_bn"
                        value="<?= $metaVal('title_bn') ?>"
                        placeholder="বাংলায় পণ্যের নাম"
                        lang="bn"
                        class="w-full font-sans text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md placeholder:text-subtle"
                    >
                    <p class="text-[10px] text-subtle mt-1 font-mono">Saved as meta → title_bn</p>
                </div>

            </div>
        </div>

        <!-- Core fields -->
        <div class="card">
            <div class="card-head"><span class="card-title">Core info</span></div>
            <div class="p-5 grid grid-cols-2 gap-4">

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">SKU</label>
                    <input
                        type="text"
                        name="sku"
                        value="<?= $val('sku') ?>"
                        placeholder="e.g. RES-0402-10K"
                        class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md placeholder:text-subtle"
                    >
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">Permalink</label>
                    <input
                        type="text"
                        name="permalink"
                        value="<?= $val('permalink') ?>"
                        placeholder="/product/my-product"
                        class="w-full font-mono text-[12px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md placeholder:text-subtle"
                    >
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">WooCommerce ID</label>
                    <input
                        type="number"
                        name="wc_id"
                        value="<?= $val('wc_id') ?>"
                        placeholder="0"
                        class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md placeholder:text-subtle"
                    >
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">WC Created date</label>
                    <input
                        type="datetime-local"
                        name="wc_created_at"
                        value="<?= $isEdit && $product?->wc_created_at ? date('Y-m-d\TH:i', strtotime($product->wc_created_at)) : '' ?>"
                        class="w-full font-mono text-[12px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md"
                    >
                </div>

            </div>
        </div>

        <!-- Pricing -->
        <div class="card">
            <div class="card-head"><span class="card-title">Pricing</span></div>
            <div class="p-5 grid grid-cols-3 gap-4">

                <?php
                $prices = [
                    'price_regular' => ['label' => 'Regular price', 'placeholder' => '0.00'],
                    'price_offer'   => ['label' => 'Sale / offer price', 'placeholder' => '0.00'],
                    'price_buy'     => ['label' => 'Cost of goods (buy)', 'placeholder' => '0.00'],
                ];
                foreach ($prices as $field => $cfg):
                ?>
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5"><?= $cfg['label'] ?></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[13px] text-subtle">৳</span>
                        <input
                            type="number"
                            step="0.01"
                            name="<?= $field ?>"
                            value="<?= $val($field) ?>"
                            placeholder="<?= $cfg['placeholder'] ?>"
                            class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md pl-7 pr-3 py-2 outline-none focus:border-border-md placeholder:text-subtle"
                        >
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        </div>

        <!-- Stock -->
        <div class="card">
            <div class="card-head"><span class="card-title">Stock</span></div>
            <div class="p-5 grid grid-cols-2 gap-4">

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">Status</label>
                    <?php
                    $stockStatus = old('stock_status', $product?->stock_status ?? 'outofstock');
                    $stockOptions = ['instock' => 'In stock', 'outofstock' => 'Out of stock', 'onbackorder' => 'On backorder'];
                    ?>
                    <select
                        name="stock_status"
                        class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md"
                    >
                        <?php foreach ($stockOptions as $val_ => $label): ?>
                        <option value="<?= $val_ ?>" <?= $stockStatus === $val_ ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">Quantity</label>
                    <input
                        type="number"
                        name="stock_quantity"
                        value="<?= $val('stock_quantity') ?>"
                        placeholder="0"
                        class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md placeholder:text-subtle"
                    >
                </div>

            </div>
        </div>

        <!-- SEO -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">SEO</span>
                <span class="placeholder-note">meta → seo</span>
            </div>
            <div class="p-5 flex flex-col gap-4">

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">
                        SEO Title
                    </label>
                    <input
                        type="text"
                        name="meta_seo_title"
                        value="<?= $seoTitle ?>"
                        placeholder="Leave empty to use product title"
                        class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md placeholder:text-subtle"
                    >
                    <p class="text-[10px] text-subtle mt-1 font-mono">Saved as meta → seo['title']</p>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">
                        SEO Description
                    </label>
                    <textarea
                        name="meta_seo_description"
                        rows="3"
                        placeholder="Brief description for search engines (150–160 chars)"
                        class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md placeholder:text-subtle resize-none"
                    ><?= $seoDesc ?></textarea>
                    <p class="text-[10px] text-subtle mt-1 font-mono">Saved as meta → seo['description']</p>
                </div>

            </div>
        </div>

    </div>

    <!-- ── Right sidebar (1/3) ── -->
    <div class="flex flex-col gap-4">

        <!-- Save actions -->
        <div class="card">
            <div class="card-head"><span class="card-title"><?= $isEdit ? 'Update' : 'Publish' ?></span></div>
            <div class="p-4 flex flex-col gap-3">
                <button
                    type="submit"
                    class="w-full font-mono text-[13px] text-surface bg-text rounded-md px-3 py-2 cursor-pointer hover:opacity-80 transition-opacity border-none"
                >
                    <?= $isEdit ? 'save changes' : 'create product' ?>
                </button>
                <a
                    href="<?= $isEdit && $product ? '/products/preview?id=' . $product->id : '/products' ?>"
                    class="block text-center font-mono text-[12px] text-subtle no-underline hover:text-muted transition-colors"
                >
                    cancel
                </a>
            </div>
        </div>

        <!-- Status card — only relevant in edit -->
        <?php if ($isEdit && $product): ?>
        <div class="card">
            <div class="card-head"><span class="card-title">Record</span></div>
            <div class="p-4 flex flex-col gap-3">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Internal ID</div>
                    <div class="font-mono text-[13px] text-text">#<?= $product->id ?></div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">First synced</div>
                    <div class="font-mono text-[11px] text-text"><?= date('d M Y, H:i', strtotime($product->created_at)) ?></div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Last synced</div>
                    <div class="font-mono text-[11px] text-text"><?= date('d M Y, H:i', strtotime($product->updated_at)) ?></div>
                </div>
                <a
                    href="/products/preview?id=<?= $product->id ?>"
                    class="font-mono text-[11px] text-info no-underline hover:underline"
                >
                    ← back to preview
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>

<?= form_close() ?>

<?= $this->endSection() ?>