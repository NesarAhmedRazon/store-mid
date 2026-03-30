<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/*
 * ─────────────────────────────────────────────────────────
 * DUMMY DATA — remove once these fields are implemented
 * ─────────────────────────────────────────────────────────
 */
$dummy_main_image = 'https://res.cloudinary.com/dgktjxcrh/image/upload/v1774703111/Ti-TPS54202DDCR_thumbnail_smdpicker_com_idpbid.jpg';

$dummy_gallery = [
    'https://res.cloudinary.com/dgktjxcrh/image/upload/v1774459302/6.webp',
    'https://res.cloudinary.com/dgktjxcrh/image/upload/v1774458693/Battery-Management-IC-TP4056-3-smdpicker.com_.webp',
    'https://res.cloudinary.com/dgktjxcrh/image/upload/v1774458496/Raspberry-Pi-Pico_1_smdpicker.com_.webp',
    'https://res.cloudinary.com/dgktjxcrh/image/upload/v1774458460/ESP32-C6-WROOM-1-MAN8-Development-Board_1_smdpicker.com_.webp',
];

$image_data = ['thumb' => $dummy_main_image,'gallery'=>$dummy_gallery];

$dummy_categories = [
    ['name' => 'Electronic Components', 'parent' => null],
    ['name' => 'Passive Components',    'parent' => 'Electronic Components'],
    ['name' => 'Crystal Oscillators',   'parent' => 'Passive Components'],
];

$dummy_attributes = [
    ['name' => 'Frequency',           'value' => '40 MHz'],
    ['name' => 'Frequency Tolerance', 'value' => '±10 ppm'],
    ['name' => 'Package',             'value' => 'SMD 4-Pad (4.0 × 2.5 mm)'],
    ['name' => 'Load Capacitance',    'value' => '12 pF'],
    ['name' => 'Operating Temp.',     'value' => '-20°C to +70°C'],
    ['name' => 'Supply Voltage',      'value' => '3.3V'],
    ['name' => 'Output Type',         'value' => 'HCMOS / TTL'],
    ['name' => 'RoHS',                'value' => 'Compliant'],
];

$dummy_tags = [
    'crystal', 'oscillator', '40mhz', 'smd', 'passive', 'timing', 'hcmos', 'ttl',
];

$attributes = $product->attributes;
$cost = $product->cost;

log_message('debug',print_r($attributes));

$statusBadge = [
    'instock'     => 'up',
    'outofstock'  => 'down',
    'onbackorder' => 'warn',
];
?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-[12px] font-mono text-subtle mb-5">
    <a href="/products" class="hover:text-muted transition-colors no-underline">Products</a>
    <span>/</span>
    <span class="text-text"><?= esc($product->title) ?></span>
</div>

<!-- Action bar -->
<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-2">
        <span class="badge badge-<?= $statusBadge[$product->stock_status] ?? 'info' ?>"><?= esc($product->stock_status) ?></span>
        <span class="text-[12px] font-mono text-subtle">#<?= esc($product->wc_id) ?></span>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= esc($product->permalink) ?>" target="_blank"
           class="text-[11px] font-mono text-subtle no-underline px-2.5 py-1 border border-border rounded hover:border-border-md hover:text-muted transition-colors">
            view on store ↗
        </a>
        <a href="/products?edit=<?= $product->id ?>"
           class="text-[11px] font-mono text-text no-underline px-2.5 py-1 border border-border-md rounded hover:bg-bg transition-colors">
            edit →
        </a>
    </div>
</div>

<div class="grid grid-cols-3 gap-4">

    <!-- ── Left / main column ── -->
    <div class="col-span-2 flex flex-col gap-4">

        <!-- Images -->
        <?= view('products/widgets/images', ['data' => $image_data ?? []],['saveData' => false]) ?> 

        <!-- Product info -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Product info</span>
            </div>
            <div class="p-5 flex flex-col gap-4">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Title</div>
                    <div class="text-[15px] font-medium text-text"><?= esc($product->title) ?></div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">SKU</div>
                        <div class="font-mono text-[13px] text-text"><?= $product->sku ? esc($product->sku) : '—' ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">WooCommerce ID</div>
                        <div class="font-mono text-[13px] text-text">#<?= esc($product->wc_id) ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">WC Created</div>
                        <div class="font-mono text-[13px] text-text">
                            <?= $product->wc_created_at ? date('d M Y', strtotime($product->wc_created_at)) : '—' ?>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Permalink</div>
                    <a href="<?= esc($product->permalink) ?>" target="_blank"
                       class="font-mono text-[12px] text-info no-underline hover:underline break-all">
                        <?= esc($product->permalink) ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Attributes -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Attributes</span>
                <span class="placeholder-note">not implemented</span>
            </div>
            <table class="w-full border-collapse">
                <?php foreach ($attributes as $attr): ?>
                <tr class="border-b border-border last:border-0 hover:bg-bg transition-colors">
                    <td class="px-4 py-2.5 text-[10px] uppercase tracking-widest text-subtle font-medium w-44 whitespace-nowrap">
                        <?= esc($attr['name']) ?>
                    </td>
                    <td class="px-4 py-2.5 font-mono text-[12px] text-text">
                        <?= esc($attr['value']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Pricing -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Pricing</span>
            </div>
            <div class="p-5 grid grid-cols-3 gap-6">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Regular price</div>
                    <div class="font-mono text-[22px] font-light text-text">
                        ৳<?= number_format($product->regular_price, 2) ?>
                    </div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Sale price</div>
                    <?php if ($product->sale_price !== null): ?>
                    <div class="font-mono text-[22px] font-light text-up">
                        ৳<?= number_format($product->sale_price, 2) ?>
                    </div>
                    <?php else: ?>
                    <div class="font-mono text-[22px] font-light text-subtle">—</div>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Cost of goods</div>
                    <?php if ($cost !== null): ?>
                    <div class="font-mono text-[22px] font-light text-muted">
                        ৳ <?= format_decimal($cost) ?>
                    </div>
                    <?php else: ?>
                    <div class="font-mono text-[22px] font-light text-subtle">—</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            $activePriceForMargin = $product->sale_price ?? $product->regular_price;
            $margin    = $activePriceForMargin - $cost;
            $marginPct = $cost > 0 ? ($margin / $cost) * 100 : 0;
            ?>
            <div class="px-5 pb-5 pt-0 flex items-center gap-4 border-t border-border mt-0">
                <div class="text-[11px] text-subtle mt-4">
                    Margin on <?= $product->sale_price !== null ? 'sale' : 'regular' ?> price:
                    <span class="font-mono font-medium <?= $margin >= 0 ? 'text-up' : 'text-down' ?>">
                        ৳<?= number_format($margin, 2) ?> (<?= number_format($marginPct, 1) ?>%)
                    </span>
                </div>
            </div>
        </div>

    </div>

    <!-- ── Right / sidebar column ── -->
    <div class="flex flex-col gap-4">

        <!-- Stock -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Stock</span>
            </div>
            <div class="p-5 flex flex-col gap-4">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Status</div>
                    <span class="badge badge-<?= $statusBadge[$product->stock_status] ?? 'info' ?>">
                        <?= esc($product->stock_status) ?>
                    </span>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Quantity</div>
                    <div class="font-mono text-[32px] font-light text-text leading-none">
                        <?= $product->stock_quantity !== null ? number_format($product->stock_quantity) : '—' ?>
                    </div>
                    <div class="text-[10px] text-subtle mt-1">units in stock</div>
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Categories</span>
                <span class="placeholder-note">not implemented</span>
            </div>
            <div class="p-4 flex flex-col gap-1.5">
                <?php foreach ($dummy_categories as $i => $cat): ?>
                <div class="flex items-center gap-1.5" style="padding-left: <?= $i * 14 ?>px">
                    <?php if ($i > 0): ?>
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none" class="shrink-0 text-subtle">
                        <path d="M2 2v4h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php endif; ?>
                    <span class="text-[12px] <?= $i === count($dummy_categories) - 1 ? 'text-text font-medium' : 'text-muted' ?>">
                        <?= esc($cat['name']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

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