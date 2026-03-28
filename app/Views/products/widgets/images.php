<?php
$thumb   = $data['thumb']   ?? null;
$gallery = $data['gallery'] ?? [];
?>

<div class="card">
    <div class="card-head">
        <span class="card-title">Images</span>
        <span class="placeholder-note">not implemented</span>
    </div>
    <div class="p-4 flex gap-4">

        <!-- Main image -->
        <div class="w-48 h-48 shrink-0 rounded-lg border border-border overflow-hidden bg-bg">
            <?php if ($thumb): ?>
            <img src="<?= esc($thumb) ?>" alt="Main image" class="w-full h-full object-cover">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-[11px] font-mono text-subtle">
                no image
            </div>
            <?php endif; ?>
        </div>

        <!-- Gallery -->
        <?php if (!empty($gallery)): ?>
        <div class="flex-1">
            <div class="text-[10px] uppercase tracking-widest text-subtle mb-2">Gallery</div>
            <div class="grid grid-cols-4 gap-2">
                <?php foreach ($gallery as $img): ?>
                <div class="aspect-square rounded-md border border-border overflow-hidden bg-bg hover:border-border-md transition-colors cursor-pointer">
                    <img src="<?= esc($img) ?>" alt="" class="w-full h-full object-cover">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>