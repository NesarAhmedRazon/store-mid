<?php
/**
 * views/categories/index.php
 *
 * Expects:
 *   $categories — flat array of category objects sorted by path (from CategoryModel::getTree())
 *   $title      — page title string
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="flex items-center gap-2.5 bg-down-bg border border-down/20 text-down text-[13px] px-4 py-3 rounded-lg mb-5">
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.4"/><path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    <?= esc(session()->getFlashdata('error')) ?>
</div>
<?php endif; usort($categories, fn($a, $b) => strcmp($a->path, $b->path));
?>
<!-- Action bar -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
    <div class="flex items-center gap-2">
        <span class="text-[10px] uppercase tracking-widest text-subtle shrink-0">Total</span>
        <span class="font-mono text-[12px] text-text"><?= count($categories) ?> categories</span>
    </div>
    <div class="flex items-center gap-2 w-full sm:w-auto">
        <input
            type="text"
            id="cat-search"
            placeholder="search categories..."
            class="flex-1 sm:w-52 text-[12px] font-mono px-3 py-1.5 border border-border rounded-md bg-transparent text-text placeholder:text-subtle focus:outline-none focus:border-border-md"
        >
        <a href="/categories/create"
           class="shrink-0 text-[11px] font-mono text-text no-underline px-3 py-1.5 border border-border-md rounded-md hover:bg-bg transition-colors">
            + new
        </a>
    </div>
</div>

<!-- Tree table -->
<div class="card overflow-hidden">
    <div class="card-head">
        <span class="card-title">Categories</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-[12px]" id="cat-table">
            <thead>
                <tr class="border-b border-border">
                    <th class="text-left font-mono text-[10px] uppercase tracking-widest text-subtle px-4 py-2.5">Name</th>
                    <th class="text-left font-mono text-[10px] uppercase tracking-widest text-subtle px-4 py-2.5 hidden sm:table-cell">Slug</th>
                    <th class="text-center font-mono text-[10px] uppercase tracking-widest text-subtle px-4 py-2.5">Products</th>
                    <th class="text-center font-mono text-[10px] uppercase tracking-widest text-subtle px-4 py-2.5 hidden md:table-cell">Hirercy</th>
                    <th class="text-right font-mono text-[10px] uppercase tracking-widest text-subtle px-4 py-2.5">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center font-mono text-[11px] text-subtle">
                        no categories yet
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($categories as $cat): ?>
                <tr class="border-b border-border last:border-0 hover:bg-bg transition-colors cat-row"
                    data-name="<?= esc(strtolower($cat->name)) ?>"
                    data-slug="<?= esc(strtolower($cat->slug)) ?>">

                    <!-- Name with depth indent -->
                    <td class="px-4 py-2.5">
                        <div class="flex items-center gap-1.5"
                             style="padding-left: <?= (int) $cat->depth * 18 ?>px">
                            <?php if ($cat->depth > 0): ?>
                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none" class="shrink-0 text-subtle">
                                <path d="M2 2v4h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <?php endif; ?>
                            <a href="/categories/<?= $cat->id ?>"
                               class="text-text font-medium no-underline hover:underline">
                                <?= esc($cat->name) ?>
                            </a>
                        </div>
                    </td>

                    <!-- Slug -->
                    <td class="px-4 py-2.5 hidden sm:table-cell">
                        <span class="font-mono text-subtle"><?= esc($cat->slug) ?></span>
                    </td>

                    <!-- Depth -->
                    <td class="px-4 py-2.5">
                        <span class="font-mono block text-subtle text-center"><?= $cat->product_count ?></span>
                    </td>
                    <!-- Path -->
                    <td class="px-4 py-2.5 hidden md:table-cell text-center">
                        <span class="font-mono text-subtle"><?= esc($cat->path) ?></span>
                    </td>


                    <!-- Actions -->
                    <td class="px-4 py-2.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="/categories/<?= $cat->id ?>"
                               class="font-mono text-[11px] text-subtle no-underline hover:text-text transition-colors">view</a>
                            <a href="/categories/<?= $cat->id ?>/edit"
                               class="font-mono text-[11px] text-subtle no-underline hover:text-text transition-colors">edit</a>
                            <button
                                onclick="confirmDelete(<?= $cat->id ?>, '<?= esc($cat->name) ?>')"
                                class="font-mono text-[11px] text-subtle hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-0">
                                delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete confirm modal -->
<div id="delete-modal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/30">
    <div class="bg-surface border border-border rounded-lg p-6 w-80 shadow-sm">
        <p class="text-[13px] text-text mb-1">Delete category?</p>
        <p class="text-[11px] font-mono text-subtle mb-5" id="delete-modal-name"></p>
        <div class="flex gap-2 justify-end">
            <button onclick="closeModal()"
                    class="text-[11px] font-mono px-3 py-1.5 border border-border rounded-md text-muted hover:text-text hover:border-border-md transition-colors bg-transparent cursor-pointer">
                cancel
            </button>
            <a id="delete-confirm-btn"
               href="#"
               class="text-[11px] font-mono px-3 py-1.5 border border-red-400 rounded-md text-red-500 no-underline hover:bg-red-50 transition-colors">
                delete
            </a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<script>
// ── Search ───────────────────────────────────────────────────────────────
document.getElementById('cat-search').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.cat-row').forEach(function (row) {
        const match = !q
            || row.dataset.name.includes(q)
            || row.dataset.slug.includes(q);
        row.style.display = match ? '' : 'none';
    });
});

// ── Delete modal ─────────────────────────────────────────────────────────
function confirmDelete(id, name) {
    document.getElementById('delete-modal-name').textContent = name;
    document.getElementById('delete-confirm-btn').href = '/categories/' + id + '/delete';
    document.getElementById('delete-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('delete-modal').classList.add('hidden');
}

document.getElementById('delete-modal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
});
</script>
