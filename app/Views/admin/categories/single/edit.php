<?php
/**
 * views/admin/categories/single/edit.php
 *
 * Expects:
 *   $category      — category object being edited
 *   $allCategories — flat sorted array for parent dropdown (must exclude self + descendants)
 *   $errors        — validation errors array
 *   $title         — page title
 */
$errors = $errors ?? [];
?>

<!-- Action bar -->
<div class="flex items-center justify-between mb-5">
    <a href="/admin/categories/<?= $category->id ?>"
       class="text-[11px] font-mono text-subtle no-underline hover:text-text transition-colors">
        ← back to category
    </a>
    <button
        onclick="confirmDelete(<?= $category->id ?>, '<?= esc($category->name) ?>')"
        class="text-[11px] font-mono text-subtle hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-0">
        delete
    </button>
</div>

<form action="/admin/categories/<?= $category->id ?>/update" method="POST" class="flex flex-col gap-4 max-w-2xl">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PUT">

    <!-- Name + Slug -->
    <div class="card">
        <div class="card-head">
            <span class="card-title">Identity</span>
            <?php if ($category->wc_id): ?>
            <span class="text-[10px] font-mono text-subtle">wc_id: <?= $category->wc_id ?></span>
            <?php endif; ?>
        </div>
        <div class="p-4 flex flex-col gap-4">

            <!-- Name -->
            <div class="flex flex-col gap-1.5">
                <label class="text-[10px] uppercase tracking-widest font-mono text-subtle">Name</label>
                <input
                    type="text"
                    name="name"
                    id="cat-name"
                    value="<?= esc(old('name', $category->name)) ?>"
                    class="text-[13px] px-3 py-2 border <?= isset($errors['name']) ? 'border-red-400' : 'border-border' ?> rounded-md bg-transparent text-text focus:outline-none focus:border-border-md"
                >
                <?php if (isset($errors['name'])): ?>
                <span class="text-[11px] font-mono text-red-500"><?= esc($errors['name']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Slug -->
            <div class="flex flex-col gap-1.5">
                <label class="text-[10px] uppercase tracking-widest font-mono text-subtle">Slug</label>
                <input
                    type="text"
                    name="slug"
                    id="cat-slug"
                    value="<?= esc(old('slug', $category->slug)) ?>"
                    class="text-[13px] font-mono px-3 py-2 border <?= isset($errors['slug']) ? 'border-red-400' : 'border-border' ?> rounded-md bg-transparent text-text focus:outline-none focus:border-border-md"
                >
                <?php if (isset($errors['slug'])): ?>
                <span class="text-[11px] font-mono text-red-500"><?= esc($errors['slug']) ?></span>
                <?php endif; ?>
                <?php if ($category->wc_id): ?>
                <span class="text-[10px] font-mono text-subtle">changing slug on a WC-synced category may break re-sync</span>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Parent + Description -->
    <div class="card">
        <div class="card-head">
            <span class="card-title">Hierarchy</span>
        </div>
        <div class="p-4 flex flex-col gap-4">

            <!-- Current path (read-only) -->
            <div class="flex flex-col gap-1.5">
                <label class="text-[10px] uppercase tracking-widest font-mono text-subtle">Current path</label>
                <span class="text-[12px] font-mono text-subtle"><?= esc($category->path) ?></span>
                <span class="text-[10px] font-mono text-subtle">path and depth are recalculated automatically on parent change</span>
            </div>

            <!-- Parent — self and descendants excluded in controller -->
            <div class="flex flex-col gap-1.5">
                <label class="text-[10px] uppercase tracking-widest font-mono text-subtle">Parent category</label>
                <select
                    name="parent_id"
                    class="text-[12px] font-mono px-3 py-2 border border-border rounded-md bg-transparent text-text focus:outline-none focus:border-border-md"
                >
                    <option value="">— none (root) —</option>
                    <?php foreach ($allCategories ?? [] as $cat): ?>
                    <option value="<?= $cat->id ?>"
                            <?= (int) old('parent_id', $category->parent_id ?? 0) === $cat->id ? 'selected' : '' ?>>
                        <?= str_repeat('— ', (int) $cat->depth) ?><?= esc($cat->name) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['parent_id'])): ?>
                <span class="text-[11px] font-mono text-red-500"><?= esc($errors['parent_id']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-1.5">
                <label class="text-[10px] uppercase tracking-widest font-mono text-subtle">Description <span class="normal-case tracking-normal">(optional)</span></label>
                <textarea
                    name="description"
                    rows="4"
                    class="text-[13px] px-3 py-2 border border-border rounded-md bg-transparent text-text placeholder:text-subtle focus:outline-none focus:border-border-md resize-none"
                ><?= esc(old('description', $category->description ?? '')) ?></textarea>
            </div>

        </div>
    </div>

    <!-- Submit -->
    <div class="flex items-center justify-end gap-2">
        <a href="/admin/categories/<?= $category->id ?>"
           class="text-[11px] font-mono text-muted no-underline px-3 py-1.5 border border-border rounded-md hover:text-text hover:border-border-md transition-colors">
            cancel
        </a>
        <button
            type="submit"
            class="text-[11px] font-mono text-text px-4 py-1.5 border border-border-md rounded-md hover:bg-bg transition-colors cursor-pointer bg-transparent">
            save changes →
        </button>
    </div>

</form>

<!-- Delete confirm modal -->
<div id="delete-modal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/30">
    <div class="bg-surface border border-border rounded-lg p-6 w-80">
        <p class="text-[13px] text-text mb-1">Delete category?</p>
        <p class="text-[11px] font-mono text-subtle mb-1" id="delete-modal-name"></p>
        <p class="text-[11px] font-mono text-subtle mb-5">children with this parent will be blocked (RESTRICT)</p>
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

<script>
// Auto-generate slug from name — only when slug hasn't been manually edited
(function () {
    const nameInput = document.getElementById('cat-name');
    const slugInput = document.getElementById('cat-slug');
    let slugTouched = true; // on edit, slug is pre-filled — don't auto-overwrite

    slugInput.addEventListener('input', function () {
        slugTouched = true;
    });

    nameInput.addEventListener('input', function () {
        if (slugTouched) return;
        slugInput.value = this.value
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    });
})();

function confirmDelete(id, name) {
    document.getElementById('delete-modal-name').textContent = name;
    document.getElementById('delete-confirm-btn').href = '/admin/categories/' + id + '/delete';
    document.getElementById('delete-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('delete-modal').classList.add('hidden');
}

document.getElementById('delete-modal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
});
</script>
