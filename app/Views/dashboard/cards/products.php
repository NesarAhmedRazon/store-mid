<?php 
    $products = $data ?? [];
?>
<div class="card <?= $class ?? '' ?>" >
        <div class="card-head">
            <span class="card-title">Recent products </span>
            <span class="placeholder-note">placeholder data</span>
        </div>
        <table class="w-full border-collapse" style="table-layout: auto;">
            <thead>
                <tr class="bg-bg">
                    <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">WC ID</th>
                    <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">SKU</th>
                    <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border w-full">Title</th>
                    <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-right px-4 py-2.5 border-b border-border whitespace-nowrap">Stock</th>
                    <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">Status</th>
                    <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-right px-4 py-2.5 border-b border-border whitespace-nowrap">Price</th>
                    <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">Last updated</th>
                </tr>
            </thead>
            <tbody>
                <?php
                
                $statusBadge = ['instock' => 'up', 'outofstock' => 'down', 'onbackorder' => 'warn'];
                foreach ($products as $p): ?>
                <tr class="hover:bg-bg transition-colors border-b border-border last:border-0">
                    <td class="px-4 py-2.5 font-mono text-[11px] text-subtle whitespace-nowrap">#<?= $p->id ?></td>
                    <td class="px-4 py-2.5 font-mono text-[12px] text-muted whitespace-nowrap"><?= $p->sku ?></td>
                    <td class="px-4 py-2.5 text-[12px] font-medium text-text">
                        <a href="/products/preview?id=<?= $p->id ?>" class="hover:text-info transition-colors no-underline"><?= esc($p->title) ?></a>
                    </td>
                    <td class="px-4 py-2.5 font-mono text-[12px] text-text text-right whitespace-nowrap"><?= number_format($p->stock_quantity) ?></td>
                    <td class="px-4 py-2.5 whitespace-nowrap"><span class="badge badge-<?= $statusBadge[$p->stock_status] ?>"><?= $p->stock_status ?></span></td>
                    <td class="px-4 py-2.5 font-mono text-[12px] text-right whitespace-nowrap">
                        <span class="text-up">৳<?= number_format($p->price_sell, 2) ?></span>
                        <?php if ($p->price_offer): ?><span class="text-subtle text-[11px] line-through ml-1">৳<?= number_format($p->price_regular, 2) ?></span><?php endif; ?>
                        
                    </td>
                    <td class="px-4 py-2.5 font-mono text-[11px] text-subtle whitespace-nowrap"><?= \CodeIgniter\I18n\Time::parse($p->updated_at)->humanize() ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
    </div>