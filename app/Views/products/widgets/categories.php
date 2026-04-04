<?php

/**
 * Sort by path so hierarchy order is always correct:
 * "3" < "3/1" < "3/2" < "4"
 * regardless of the order getByProduct() returns them.
 */
$categories = $data ?? [];
usort($categories, fn($a, $b) => strcmp($a->path, $b->path));
?>

<div class="card">
    <div class="card-head">
        <span class="card-title">Categories</span>
    </div>
    <div class="p-4 flex flex-col gap-1">

        <?php if (empty($categories)): ?>
            <span class="text-[11px] font-mono text-subtle">no categories</span>
        <?php endif; ?>

        <?php foreach ($categories as $cat): ?>
            <div class="flex items-center gap-1.5" style="padding-left: <?= (int) $cat->depth * 16 ?>px">

                <?php if ($cat->depth > 0): ?>
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none" class="shrink-0 text-subtle" style="flex-shrink:0">
                        <path d="M2 2v4h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                <?php endif; ?>

                <span class="text-[12px] <?= $cat->is_primary ? 'text-text font-medium' : 'text-muted' ?>">
                    <?= $cat->name ?>
                </span>

                <?php if ($cat->is_primary): ?>
                    <span class="text-[10px] font-mono text-subtle ml-1">primary</span>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    </div>
</div>