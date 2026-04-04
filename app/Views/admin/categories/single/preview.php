<?php
/**
 * views/admin/categories/single/preview.php
 *
 * Expects:
 *   $category  — category object
 *   $breadcrumb — array of ancestor objects root → current (from CategoryModel::getBreadcrumb())
 *   $children  — direct child category objects
 *   $products  — products in this category (optional, can be empty)
 *   $title     — page title
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="flex items-center gap-2.5 bg-down-bg border border-down/20 text-down text-[13px] px-4 py-3 rounded-lg mb-5">
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.4"/><path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    <?= esc(session()->getFlashdata('error')) ?>
</div>
<?php endif; 
?>
<!-- Action bar -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-1.5 flex-wrap min-w-0">
        <a href="/admin/categories" class="text-[11px] font-mono text-subtle no-underline hover:text-text transition-colors">
            categories
        </a>
        <?php foreach ($breadcrumb as $crumb): ?>
        <span class="text-subtle text-[11px]">/</span>
        <a href="/admin/categories/<?= $crumb->id ?>"
           class="text-[11px] font-mono <?= $crumb->id === $category->id ? 'text-text' : 'text-subtle' ?> no-underline hover:text-text transition-colors truncate">
            <?= esc($crumb->name) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="flex items-center gap-2 shrink-0">
        <a href="/admin/categories/<?= $category->id ?>/edit"
           class="text-[11px] font-mono text-text no-underline px-2.5 py-1 border border-border-md rounded hover:bg-bg transition-colors">
            edit →
        </a>
    </div>

</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    <!-- Left col: details -->
    <div class="md:col-span-2 flex flex-col gap-4">

        <!-- Info card -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Details</span>
            </div>
            <div class="p-4 flex flex-col gap-3">

                <div class="grid grid-cols-[120px_1fr] gap-y-3 text-[12px]">
                    <span class="font-mono text-subtle">name</span>
                    <span class="text-text font-medium"><?= esc($category->name) ?></span>

                    <span class="font-mono text-subtle">slug</span>
                    <span class="font-mono text-text"><?= esc($category->slug) ?></span>

                    <span class="font-mono text-subtle">path</span>
                    <span class="font-mono text-text"><?= esc($category->path) ?></span>

                    <span class="font-mono text-subtle">depth</span>
                    <span class="font-mono text-text"><?= (int) $category->depth ?></span>

                    <span class="font-mono text-subtle">wc_id</span>
                    <span class="font-mono text-subtle"><?= $category->wc_id ?? '—' ?></span>

                    <span class="font-mono text-subtle">created</span>
                    <span class="font-mono text-subtle"><?= date('d M Y, H:i', strtotime($category->created_at)) ?></span>

                    <span class="font-mono text-subtle">updated</span>
                    <span class="font-mono text-subtle"><?= date('d M Y, H:i', strtotime($category->updated_at)) ?></span>
                </div>

                <?php if (!empty($category->description)): ?>
                <div class="border-t border-border pt-3 mt-1">
                    <p class="text-[12px] text-muted leading-relaxed"><?= esc($category->description) ?></p>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Products in this category -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Products</span>
                <span class="text-[11px] font-mono text-subtle"><?= count($products ?? []) ?></span>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($products)): ?>
                <p class="px-4 py-6 text-[11px] font-mono text-subtle">no products in this category</p>
                <?php else: ?>
                <table class="w-full text-[12px]">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="text-left font-mono text-[10px] uppercase tracking-widest text-subtle px-4 py-2">Name</th>
                            <th class="text-left font-mono text-[10px] uppercase tracking-widest text-subtle px-4 py-2 hidden sm:table-cell">SKU</th>
                            <th class="text-left font-mono text-[10px] uppercase tracking-widest text-subtle px-4 py-2">Stock</th>
                            <th class="text-right font-mono text-[10px] uppercase tracking-widest text-subtle px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr class="border-b border-border last:border-0 hover:bg-bg transition-colors">
                            <td class="px-4 py-2.5">
                                <a href="/admin/products/<?= $product->id ?>"
                                   class="text-text no-underline hover:underline">
                                    <?= esc($product->title) ?>
                                </a>
                            </td>
                            <td class="px-4 py-2.5 hidden sm:table-cell">
                                <span class="font-mono text-subtle"><?= esc($product->sku ?? '—') ?></span>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="font-mono text-[11px] <?= $product->stock_status === 'instock' ? 'text-up' : 'text-subtle' ?>">
                                    <?= esc($product->stock_status) ?>
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <a href="/admin/products/<?= $product->id ?>"
                                   class="font-mono text-[11px] text-subtle no-underline hover:text-text transition-colors">view</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Right col: children + thumb -->
    <div class="flex flex-col gap-4">

        <!-- Thumbnail -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Image</span>
            </div>
            <div class="p-4">
                <?php if (!empty($category->thumb_id)): ?>
                <div class="aspect-square rounded-lg overflow-hidden border border-border bg-bg">
                    <img src="<?= esc($thumb->path ?? '') ?>" alt="" class="w-full h-full object-cover">
                </div>
                <?php else: ?>
                <div class="aspect-square rounded-lg border border-border bg-bg flex items-center justify-center">
                    <span class="text-[11px] font-mono text-subtle">no image</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Children -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Sub-categories</span>
                <span class="text-[11px] font-mono text-subtle"><?= count($children ?? []) ?></span>
            </div>
            <div class="p-4 flex flex-col gap-1.5">
                <?php if (empty($children)): ?>
                    <span class="text-[11px] font-mono text-subtle">none</span>
                <?php endif; ?>
                <?php foreach ($children ?? [] as $child): ?>
                <a href="/admin/categories/<?= $child->id ?>"
                   class="flex items-center gap-1.5 text-[12px] text-muted no-underline hover:text-text transition-colors">
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none" class="shrink-0 text-subtle">
                        <path d="M2 2v4h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?= esc($child->name) ?>
                </a>
                <?php endforeach; ?>
                <a href="/admin/categories/create?parent=<?= $category->id ?>"
                   class="mt-2 text-[11px] font-mono text-subtle no-underline hover:text-text transition-colors">
                    + add sub-category
                </a>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>