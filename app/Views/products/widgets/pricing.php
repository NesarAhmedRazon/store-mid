 <?php
$price   = $data ?? [];
$offer_price   = $price['offer']?? 0;
$regular_price = $price['regular']?? 0;
$cost   = $price['cost']?? null;

?>
    <div class="card">
            <div class="card-head">
                <span class="card-title">Pricing</span>
            </div>
            <div class="p-5 grid grid-cols-3 gap-6">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Regular price</div>
                    <div class="font-mono text-[22px] font-light text-text">
                        ৳ <?= number_format($regular_price, 2) ?>
                    </div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Sale price</div>
                    <?php if ($offer_price !== null): ?>
                    <div class="font-mono text-[22px] font-light text-up">
                        ৳ <?= number_format($offer_price, 2) ?>
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
            /**
             * 1. FIX: Fallback logic. 
             * Since $offer_price is 0 if missing, we check if it is greater than 0.
             */
            $activePriceForMargin = ($offer_price > 0) ? $offer_price : $regular_price;
            /**
             * 2. FIX: Margin Calculation.
             * Profit Margin % = ((Selling Price - Cost) / Selling Price) * 100
             */
            $margin    = $activePriceForMargin - $cost;
            $marginPct = ($activePriceForMargin > 0) ? ($margin / $activePriceForMargin) * 100 : 0;

            /**
             * 3. FIX: Label Logic.
             * Check if the offer price is actually being used for the display label.
             */
            $isOnSale = ($offer_price > 0 && $offer_price < $regular_price);
            ?>

            <!-- Margin Display Section -->
            <div class="px-5 pb-5 pt-0 flex items-center gap-4 border-t border-border mt-0">
                <div class="text-[11px] text-subtle mt-4">
                    <!-- Dynamic Label based on which price is active -->
                    Margin on <?= $isOnSale ? 'sale' : 'regular' ?> price:
                    
                    <!-- Color coding: text-up (green) for profit, text-down (red) for loss -->
                    <span class="font-mono font-medium <?= $margin >= 0 ? 'text-up' : 'text-down' ?>">
                        ৳<?= number_format($margin, 2) ?> (<?= number_format($marginPct, 1) ?>%)
                    </span>
                </div>
            </div>
        </div>