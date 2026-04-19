<?php
/**
 * views/admin/categories/single/create.php
 *
 * Expects:
 *   $allCategories — flat sorted array for parent dropdown (from CategoryModel::getTree())
 *   $preselectedParent — int|null  — pre-fill parent when coming from ?parent=ID
 *   $errors        — array of validation errors (CI4 validation object or plain array)
 *   $title         — page title
 */
$errors           = $errors ?? [];
$preselectedParent = (int) ($preselectedParent ?? $this->request->getGet('parent') ?? 0);
?>

<!-- Action bar -->
<div class="flex items-center justify-between mb-5">
    <a href="/products/categories"
       class="text-[11px] font-mono text-subtle no-underline hover:text-text transition-colors">
        ← back to categories
    </a>
</div>

<form action="/products/categories/store" method="POST" class="flex flex-col gap-4 max-w-2xl">
    <?= csrf_field() ?>

    <!-- Name + Slug -->
    <div class="card">
        <div class="card-head">
            <span class="card-title">Identity</span>
        </div>
        <div class="p-4 flex flex-col gap-4">

            <!-- Name -->
            <div class="flex flex-col gap-1.5">
                <label class="text-[10px] uppercase tracking-widest font-mono text-subtle">Name</label>
                <input
                    type="text"
                    name="name"
                    id="cat-name"
                    value="<?= esc(old('name')) ?>"
                    placeholder="e.g. Microcontrollers"
                    class="text-[13px] px-3 py-2 border <?= isset($errors['name']) ? 'border-red-400' : 'border-border' ?> rounded-md bg-transparent text-text placeholder:text-subtle focus:outline-none focus:border-border-md"
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
                    value="<?= esc(old('slug')) ?>"
                    placeholder="auto-generated"
                    class="text-[13px] font-mono px-3 py-2 border <?= isset($errors['slug']) ? 'border-red-400' : 'border-border' ?> rounded-md bg-transparent text-text placeholder:text-subtle focus:outline-none focus:border-border-md"
                >
                <?php if (isset($errors['slug'])): ?>
                <span class="text-[11px] font-mono text-red-500"><?= esc($errors['slug']) ?></span>
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

            <!-- Parent -->
            <div class="flex flex-col gap-1.5">
                <label class="text-[10px] uppercase tracking-widest font-mono text-subtle">Parent category</label>
                <select
                    name="parent_id"
                    class="text-[12px] font-mono px-3 py-2 border border-border rounded-md bg-transparent text-text focus:outline-none focus:border-border-md"
                >
                    <option value="">— none (root) —</option>
                    <?php foreach ($allCategories ?? [] as $cat): ?>
                    <option value="<?= $cat->id ?>"
                            <?= (int) (old('parent_id') ?: $preselectedParent) === $cat->id ? 'selected' : '' ?>>
                        <?= str_repeat('— ', (int) $cat->depth) ?><?= esc($cat->name) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-1.5">
                <label class="text-[10px] uppercase tracking-widest font-mono text-subtle">Description <span class="normal-case tracking-normal">(optional)</span></label>
                <textarea
                    name="description"
                    rows="4"
                    placeholder="Short description..."
                    class="text-[13px] px-3 py-2 border border-border rounded-md bg-transparent text-text placeholder:text-subtle focus:outline-none focus:border-border-md resize-none"
                ><?= esc(old('description')) ?></textarea>
            </div>

        </div>
    </div>

    <!-- Submit -->
    <div class="flex items-center justify-end gap-2">
        <a href="/products/categories"
           class="text-[11px] font-mono text-muted no-underline px-3 py-1.5 border border-border rounded-md hover:text-text hover:border-border-md transition-colors">
            cancel
        </a>
        <button
            type="submit"
            class="text-[11px] font-mono text-text px-4 py-1.5 border border-border-md rounded-md hover:bg-bg transition-colors cursor-pointer bg-transparent">
            create category →
        </button>
    </div>

</form>

<script>
// Auto-generate slug from name
(function () {
    const nameInput = document.getElementById('cat-name');
    const slugInput = document.getElementById('cat-slug');
    let slugTouched = slugInput.value.length > 0;

    slugInput.addEventListener('input', function () {
        slugTouched = this.value.length > 0;
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
</script>