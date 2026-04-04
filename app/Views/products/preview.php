<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/*
 * ─────────────────────────────────────────────────────────
 * DUMMY DATA — remove once these fields are implemented
 * ─────────────────────────────────────────────────────────
 */




$dummy_categories = [
    ['name' => 'Electronic Components', 'parent' => null],
    ['name' => 'Passive Components',    'parent' => 'Electronic Components'],
    ['name' => 'Crystal Oscillators',   'parent' => 'Passive Components'],
];


$dummy_tags = [
    'crystal', 'oscillator', '40mhz', 'smd', 'passive', 'timing', 'hcmos', 'ttl',
];



$image_data = ['thumb' => $product->thumb,'gallery'=>$product->gallery ?? []];
$pricing = [
    'regular_price' => $product->regular_price,
    'sale_price' => $product->sale_price,
    'cost' => $product->cost,
];
$stock = [
    'stock_status' => $product->stock_status ?? 'outofstock',
    'stock_quantity' => $product->stock_quantity ?? 0,
];
$categories = $product->categories ?? [];

$statusBadge = [
    'instock'     => 'up',
    'outofstock'  => 'down',
    'onbackorder' => 'warn',
];
?>

<!-- Breadcrumb -->
<!-- <div class="flex items-center gap-2 text-[12px] font-mono text-subtle mb-5">
    <a href="/products" class="hover:text-muted transition-colors no-underline">Products</a>
    <span>/</span>
    <span class="text-text"><?= esc($product->title) ?></span>
</div> -->

<!-- Action bar -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">

    <div class="flex items-center gap-2 min-w-0">
        <span class="text-xs uppercase tracking-widest text-subtle shrink-0">Last synced:</span>
        <span class="font-mono text-xs text-text truncate">
            <?= date('d M y, H:i A', strtotime($product->updated_at)) ?>
        </span>
    </div>

    <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto">
        <a href="<?= esc($product->permalink) ?>" target="_blank"
           class="flex-1 sm:flex-none text-center text-[11px] font-mono text-subtle no-underline px-2.5 py-1 border border-border rounded hover:border-border-md hover:text-muted transition-colors">
            view on store ↗
        </a>
        <a href="/products?edit=<?= $product->id ?>"
           class="flex-1 sm:flex-none text-center text-[11px] font-mono text-text no-underline px-2.5 py-1 border border-border-md rounded hover:bg-bg transition-colors">
            edit →
        </a>
    </div>

</div>

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">

    <!-- ── Left / main column ── -->
    <div class="col-span-1 sm:col-span-2 flex flex-col gap-4">
        <!-- Product info -->
         <?= view('products/widgets/product_info', ['data' => $product ?? []],['saveData' => false]) ?> 

        <!-- Attributes -->
        <?= view('products/widgets/attributs', ['data' => $product->attributes ?? []],['saveData' => false]) ?> 

        <!-- Pricing -->
        <?= view('products/widgets/pricing', ['data' => $pricing ?? []],['saveData' => false]) ?> 
    </div>

    <!-- ── Right / sidebar column ── -->
    <div class="col-span-1 flex flex-col gap-4">

        <!-- Images -->
        <?= view('products/widgets/images', ['data' => $image_data ?? []],['saveData' => false]) ?> 
        <!-- Stock -->        
        <?= view('products/widgets/stock', ['data' => $stock ?? []],['saveData' => false]) ?> 

        <!-- Categories -->
         <?= view('products/widgets/categories', ['data' => $categories ?? []],['saveData' => false]) ?> 
        

        <!-- Tags -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Tags</span>
                <span class="placeholder-note">not implemented</span>
            </div>
            <div class="p-4 flex flex-wrap gap-1.5">
                <?php foreach ($dummy_tags as $tag): ?>
                <span class="font-mono text-[11px] text-muted bg-bg border border-border px-2 py-0.5 rounded-sm">
                    <?= esc($tag) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Timestamps -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Timestamps</span>
            </div>
            <div class="p-5 flex flex-col gap-4">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Created in WC</div>
                    <div class="font-mono text-[12px] text-text">
                        <?= $product->wc_created_at ? date('d M Y, H:i', strtotime($product->wc_created_at)) : '—' ?>
                    </div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">First synced</div>
                    <div class="font-mono text-[12px] text-text">
                        <?= date('d M Y, H:i', strtotime($product->created_at)) ?>
                    </div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Last synced</div>
                    <div class="font-mono text-[12px] text-text">
                        <?= date('d M Y, H:i', strtotime($product->updated_at)) ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<?= $this->endSection() ?>