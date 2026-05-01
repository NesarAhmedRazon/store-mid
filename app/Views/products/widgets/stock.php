<?php
$stock   = $data   ?? [];
$statusBadge = [
    'instock'     => 'up',
    'outofstock'  => 'down',
    'onbackorder' => 'warn',
];

?>

<div class="card">
    <div class="card-head">
        <span class="card-title">Stock</span>
    </div>
    <div class="p-5 flex flex-col gap-4">
        <div>
            <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Status</div>
            <span class="badge badge-<?= $statusBadge[$stock['status']] ?? 'info' ?>"><?= esc($stock['status']) ?></span>
        </div>
        <div>
            <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Quantity</div>
            <div class="font-mono text-[32px] font-light text-text leading-none">
                <?= $stock['quantity'] !== null ? number_format($stock['quantity']) : '—' ?>
            </div>
            <div class="text-[10px] text-subtle mt-1">units in stock</div>
        </div>
    </div>
</div>