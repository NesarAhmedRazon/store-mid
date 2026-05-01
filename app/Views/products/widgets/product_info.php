<?php 
$info   = $data   ?? [];
$stock  = $info->stock ?? [];

$statusBadge = [
    'instock'     => 'up',
    'outofstock'  => 'down',
    'onbackorder' => 'warn',
];
?>
        <div class="card">
            <div class="card-head">
                <span class="card-title">Product info</span>
            </div>
            <div class="p-5 flex flex-col gap-4">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Title</div>
                    <div class="text-[15px] font-medium text-text"><?= esc($data->title) ?></div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">SKU</div>
                        <div class="font-mono text-[13px] text-text"><?= $data->sku ? esc($data->sku) : '—' ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">WooCommerce ID</div>
                        <div class="font-mono text-[13px] text-text">#<?= esc($data->wc_id) ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">WC Created</div>
                        <div class="font-mono text-[13px] text-text">
                            <?= $data->wc_created_at ? date('d M Y', strtotime($data->wc_created_at)) : '—' ?>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Stock Status</div>
                        <span class="badge badge-<?= $statusBadge[$stock['status']] ?? 'info' ?>">
                            <?= esc($stock['status']) ?>
                        </span>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Stock Quantity</div>
                        <span class="badge badge-<?= $statusBadge[$stock['quantity']] ?? 'info' ?>">
                            <?= esc($stock['quantity'] !== null ? number_format($stock['quantity']) : '—') ?>
                        </span>
                        <div class="text-[10px] text-subtle mt-1">units in stock</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Permalink</div>
                        <a href="<?= esc($data->permalink) ?>" target="_blank"
                        class="font-mono text-[12px] text-info no-underline hover:underline break-all">
                            <?= esc($data->permalink) ?>
                        </a>
                    </div>
                </div>
                
            </div>
        </div>