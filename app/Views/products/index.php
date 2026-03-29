<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php if (session()->getFlashdata('error')): ?>
<div class="flex items-center gap-2.5 bg-down-bg border border-down/20 text-down text-[13px] px-4 py-3 rounded-lg mb-5">
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.4"/><path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    <?= esc(session()->getFlashdata('error')) ?>
</div>
<?php endif; ?>

<!-- Header row -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-[15px] font-medium text-text">Products</h2>
        <p class="text-[12px] text-subtle mt-0.5"><?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> synced from WooCommerce</p>
    </div>
</div>

<!-- Table -->
<div class="card">
    <table class="w-full border-collapse" style="table-layout: auto;">
        <thead>
            <tr class="bg-bg">
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">WC ID</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">SKU</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border w-full">Title</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-right px-4 py-2.5 border-b border-border whitespace-nowrap">Stock</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">Status</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-right px-4 py-2.5 border-b border-border whitespace-nowrap">Sale price</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-right px-4 py-2.5 border-b border-border whitespace-nowrap">Regular price</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-right px-4 py-2.5 border-b border-border whitespace-nowrap">Cost</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">WC created</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">Last synced</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
            <tr>
                <td colspan="10" class="px-4 py-10 text-center text-[13px] text-subtle font-mono">
                    No products synced yet.
                </td>
            </tr>
            <?php else: ?>
            <?php
            $statusBadge = [
                'instock'     => 'up',
                'outofstock'  => 'down',
                'onbackorder' => 'warn',
            ];
            foreach ($products as $p):
            ?>
            <tr class="hover:bg-bg transition-colors border-b border-border last:border-0 group">
                <td class="px-4 py-2.5 font-mono text-[11px] text-subtle whitespace-nowrap">
                    #<?= esc($p->wc_id) ?>
                </td>
                <td class="px-4 py-2.5 font-mono text-[12px] text-muted whitespace-nowrap">
                    <?= $p->sku ? esc($p->sku) : '<span class="text-subtle">—</span>' ?>
                </td>
                <td class="px-4 py-2.5 text-[13px] font-medium text-text">
                    <a href="/products/preview?id=<?= $p->id ?>" class="hover:text-info transition-colors no-underline">
                        <?= esc($p->title) ?>
                    </a>
                </td>
                <td class="px-4 py-2.5 font-mono text-[12px] text-text text-right whitespace-nowrap">
                    <?= $p->stock_quantity !== null ? number_format($p->stock_quantity) : '<span class="text-subtle">—</span>' ?>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="badge badge-<?= $statusBadge[$p->stock_status] ?? 'info' ?>">
                        <?= esc($p->stock_status) ?>
                    </span>
                </td>
                <td class="px-4 py-2.5 font-mono text-[12px] text-up text-right whitespace-nowrap">
                    <?= $p->sale_price !== null ? '৳' . number_format($p->sale_price, 2) : '<span class="text-subtle">—</span>' ?>
                </td>
                <td class="px-4 py-2.5 font-mono text-[12px] text-text text-right whitespace-nowrap">
                    ৳<?= number_format($p->regular_price, 2) ?>
                </td>
                <td class="px-4 py-2.5 font-mono text-[12px] text-text text-right whitespace-nowrap">
                    ৳<?= number_format($p->cost, 10) ?>
                </td>
                <td class="px-4 py-2.5 font-mono text-[11px] text-subtle whitespace-nowrap">
                    <?= $p->wc_created_at ? date('d M Y', strtotime($p->wc_created_at)) : '<span class="text-subtle">—</span>' ?>
                </td>
                <td class="px-4 py-2.5 font-mono text-[11px] text-subtle whitespace-nowrap">
                    <?= date('d M Y, H:i', strtotime($p->updated_at)) ?>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <a
                        href="/products/preview?id=<?= $p->id ?>"
                        class="text-[11px] font-mono text-subtle no-underline px-2.5 py-1 border border-border rounded hover:border-border-md hover:text-muted transition-colors opacity-0 group-hover:opacity-100"
                    >
                        view →
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>