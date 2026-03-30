 <?php
$regular_price   = $data['regular_price'] ?? 0;
$sale_price   = $data['sale_price'] ?? null;
$cost   = $data['cost'] ?? null;
?>
    <div class="card">
            <div class="card-head">
                <span class="card-title">Pricing</span>
            </div>
            <div class="p-5 grid grid-cols-3 gap-6">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Regular price</div>
                    <div class="font-mono text-[22px] font-light text-text">
                        ৳<?= number_format($regular_price, 2) ?>
                    </div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Sale price</div>
                    <?php if ($sale_price !== null): ?>
                    <div class="font-mono text-[22px] font-light text-up">
                        ৳<?= number_format($sale_price, 2) ?>
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
            $activePriceForMargin = $sale_price ?? $regular_price;
            $margin    = $activePriceForMargin - $cost;
            $marginPct = $cost > 0 ? ($margin / $cost) * 100 : 0;
            ?>
            <div class="px-5 pb-5 pt-0 flex items-center gap-4 border-t border-border mt-0">
                <div class="text-[11px] text-subtle mt-4">
                    Margin on <?= $sale_price !== null ? 'sale' : 'regular' ?> price:
                    <span class="font-mono font-medium <?= $margin >= 0 ? 'text-up' : 'text-down' ?>">
                        ৳<?= number_format($margin, 2) ?> (<?= number_format($marginPct, 1) ?>%)
                    </span>
                </div>
            </div>
        </div>