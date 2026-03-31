<?php
/**
 * Product image gallery component
 *
 * Expects:
 *   $thumb   — media object|null   (disk=url → path is full URL)
 *   $gallery — media object[]      (ordered by sort_order)
 *
 * Logic:
 *   count($gallery) >= 2 → Style B (hero + horizontal strip)
 *   count($gallery) <  2 → Style C (mosaic: main + optional side)
 */

$thumb   = $data['thumb']   ?? null;
$gallery = $data['gallery'] ?? [];

$galleryCount = count($gallery);
$useStyleB    = $galleryCount >= 2;
?>

<div class="card">
    <div class="card-head">
        <span class="card-title">Images</span>
    </div>

    <?php if ($useStyleB): ?>
    <?php /* ── Style B — Hero + horizontal strip (2+ gallery images) ─────── */ ?>

    <div class="p-0 overflow-hidden">

        <?php /* Main image — swaps on thumb click */ ?>
        <div class="w-full aspect-square overflow-hidden bg-bg" id="gallery-hero">
            <?php if ($thumb): ?>
            <img src="<?= esc($thumb->path) ?>"
                 alt="<?= esc($thumb->alt ?? '') ?>"
                 class="w-full h-full object-cover"
                 id="gallery-hero-img">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-[11px] font-mono text-subtle">
                no image
            </div>
            <?php endif; ?>
        </div>

        <?php /* Thumbnail strip */ ?>
        <?php /* Strip: 4 items visible, remainder scrolls. Each item = (100% - 3×gap) / 4 */ ?>
        <div class="overflow-x-auto p-3 scrollbar-thin">
            <div class="flex gap-2" id="gallery-strip">

                <?php /* Thumbnail is always the first strip item */ ?>
                <?php if ($thumb): ?>
                <button type="button"
                        class="gallery-strip-item shrink-0 rounded-md border-2 border-primary overflow-hidden bg-bg"
                        style="width: calc((100% - 1.5rem) / 4); aspect-ratio: 1/1;"
                        data-src="<?= esc($thumb->path) ?>"
                        data-alt="<?= esc($thumb->alt ?? '') ?>">
                    <img src="<?= esc($thumb->path) ?>" alt="" class="w-full h-full object-cover">
                </button>
                <?php endif; ?>

                <?php foreach ($gallery as $img): ?>
                <button type="button"
                        class="gallery-strip-item shrink-0 rounded-md border-2 border-transparent overflow-hidden bg-bg"
                        style="width: calc((100% - 1.5rem) / 4); aspect-ratio: 1/1;"
                        data-src="<?= esc($img->path) ?>"
                        data-alt="<?= esc($img->alt ?? '') ?>">
                    <img src="<?= esc($img->path) ?>" alt="" class="w-full h-full object-cover">
                </button>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

    <script>
    (function () {
        const heroImg = document.getElementById('gallery-hero-img');
        const items   = document.querySelectorAll('.gallery-strip-item');

        if (!heroImg) return;

        items.forEach(function (btn) {
            btn.addEventListener('click', function () {
                heroImg.src = btn.dataset.src;
                heroImg.alt = btn.dataset.alt;
                items.forEach(function (b) {
                    b.classList.remove('border-primary');
                    b.classList.add('border-transparent');
                });
                btn.classList.remove('border-transparent');
                btn.classList.add('border-primary');
            });
        });
    })();
    </script>

    <?php else: ?>
    <?php /* ── Style C — Mosaic (0 or 1 gallery image) ──────────────────── */ ?>

    <div class="p-4">
        <?php if ($galleryCount === 0): ?>

            <?php /* No gallery — thumbnail full width */ ?>
            <div class="w-full aspect-square rounded-lg overflow-hidden bg-bg border border-border">
                <?php if ($thumb): ?>
                <img src="<?= esc($thumb->path) ?>"
                     alt="<?= esc($thumb->alt ?? '') ?>"
                     class="w-full h-full object-cover">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-[11px] font-mono text-subtle">
                    no image
                </div>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <?php /* 1 gallery image — main left (2/3) + side right (1/3) */ ?>
            <div class="grid gap-2" style="grid-template-columns: 2fr 1fr;">

                <div class="aspect-square rounded-lg overflow-hidden bg-bg border border-border">
                    <?php if ($thumb): ?>
                    <img src="<?= esc($thumb->path) ?>"
                         alt="<?= esc($thumb->alt ?? '') ?>"
                         class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-[11px] font-mono text-subtle">
                        no image
                    </div>
                    <?php endif; ?>
                </div>

                <div class="aspect-square rounded-lg overflow-hidden bg-bg border border-border">
                    <img src="<?= esc($gallery[0]->path) ?>"
                         alt="<?= esc($gallery[0]->alt ?? '') ?>"
                         class="w-full h-full object-cover">
                </div>

            </div>

        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>