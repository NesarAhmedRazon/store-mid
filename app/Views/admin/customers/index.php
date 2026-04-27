<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$statusBadge = [
    'active'   => 'up',
    'inactive' => 'warn',
    'banned'   => 'down',
];
$sourceBadge = [
    'wp_import' => 'info',
    'google'    => 'info',
    'facebook'  => 'info',
    'manual'    => 'warn',
];
?>

<!-- Flash messages -->
<?php if (session()->getFlashdata('success')): ?>
<div class="flex items-center gap-2.5 bg-up-bg border border-up/20 text-up text-[13px] px-4 py-3 rounded-lg mb-5">
    <?= esc(session()->getFlashdata('success')) ?>
</div>
<?php endif; ?>

<!-- Header row -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-[15px] font-medium text-text">Customers</h2>
        <p class="text-[12px] text-subtle mt-0.5"><?= number_format($total) ?> customer<?= $total !== 1 ? 's' : '' ?> total</p>
    </div>
    <a href="/customers/new"
       class="text-[12px] font-mono text-surface no-underline px-3 py-1.5 bg-text rounded-md hover:opacity-80 transition-opacity">
        + new customer
    </a>
</div>

<!-- Filters -->
<div class="flex items-center gap-3 mb-4">
    <form method="get" class="flex items-center gap-2 flex-1">
        <input
            type="text"
            name="search"
            value="<?= esc($search ?? '') ?>"
            placeholder="Search name or email..."
            class="font-mono text-[12px] text-text bg-surface border border-border rounded-md px-3 py-1.5 outline-none focus:border-border-md w-64 placeholder:text-subtle"
        >
        <select name="status" class="font-mono text-[12px] text-text bg-surface border border-border rounded-md px-3 py-1.5 outline-none focus:border-border-md">
            <option value="">All statuses</option>
            <option value="active"   <?= $status === 'active'   ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="banned"   <?= $status === 'banned'   ? 'selected' : '' ?>>Banned</option>
        </select>
        <button type="submit" class="font-mono text-[12px] text-muted bg-transparent border border-border rounded-md px-3 py-1.5 cursor-pointer hover:border-border-md hover:text-text transition-colors">
            filter
        </button>
        <?php if ($search || $status): ?>
        <a href="/customers" class="font-mono text-[11px] text-subtle hover:text-muted transition-colors no-underline">
            clear
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Table -->
<div class="card">
    <table class="w-full border-collapse" style="table-layout: auto;">
        <thead>
            <tr class="bg-bg">
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">Name</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border w-full">Email</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">Phone</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">Source</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">Status</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">Last login</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border whitespace-nowrap">Joined</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium px-4 py-2.5 border-b border-border whitespace-nowrap"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-[13px] text-subtle font-mono">
                    No customers found<?= $search ? ' matching "' . esc($search) . '"' : '' ?>.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($customers as $c): ?>
            <tr class="hover:bg-bg transition-colors border-b border-border last:border-0 group">
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex items-center gap-2.5">
                        <?php if ($c->avatar_url): ?>
                        <img src="<?= esc($c->avatar_url) ?>" class="w-7 h-7 rounded-full object-cover shrink-0 border border-border" alt="">
                        <?php else: ?>
                        <div class="w-7 h-7 rounded-full bg-text text-white text-[10px] font-mono font-medium flex items-center justify-center shrink-0">
                            <?= strtoupper(substr($c->name, 0, 2)) ?>
                        </div>
                        <?php endif; ?>
                        <a href="/customers/<?= $c->id ?>" class="text-[13px] font-medium text-text no-underline hover:text-info transition-colors">
                            <?= esc($c->name) ?>
                        </a>
                    </div>
                </td>
                <td class="px-4 py-2.5 font-mono text-[12px] text-muted"><?= esc($c->email) ?></td>
                <td class="px-4 py-2.5 font-mono text-[12px] text-subtle whitespace-nowrap">
                    <?= $c->phone ? esc($c->phone) : '—' ?>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="badge badge-<?= $sourceBadge[$c->source] ?? 'info' ?>">
                        <?= esc($c->source) ?>
                    </span>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="badge badge-<?= $statusBadge[$c->status] ?? 'info' ?>">
                        <?= esc($c->status) ?>
                    </span>
                </td>
                <td class="px-4 py-2.5 font-mono text-[11px] text-subtle whitespace-nowrap">
                    <?= $c->last_login_at ? date('d M Y', strtotime($c->last_login_at)) : '—' ?>
                </td>
                <td class="px-4 py-2.5 font-mono text-[11px] text-subtle whitespace-nowrap">
                    <?= $c->created_at ? date('d M Y', strtotime($c->created_at)) : '—' ?>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a href="/customers/<?= $c->id ?>/edit"
                           class="text-[11px] font-mono text-subtle no-underline px-2 py-0.5 border border-border rounded hover:border-border-md hover:text-muted transition-colors">
                            edit
                        </a>
                        <a href="/customers/<?= $c->id ?>/delete"
                           class="text-[11px] font-mono text-down no-underline px-2 py-0.5 border border-down/30 rounded hover:border-down/60 transition-colors"
                           onclick="return confirm('Delete <?= esc($c->name) ?>? This cannot be undone.')">
                            del
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="px-4 py-3 border-t border-border flex items-center justify-between">
        <span class="text-[11px] font-mono text-subtle">
            Page <?= $currentPage ?> of <?= $pages ?>
            &nbsp;·&nbsp; <?= number_format($total) ?> total
        </span>
        <div class="flex items-center gap-1.5">
            <?php if ($currentPage > 1): ?>
            <a href="?page=<?= $currentPage - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . $status : '' ?>"
               class="font-mono text-[11px] text-muted no-underline px-2.5 py-1 border border-border rounded hover:border-border-md transition-colors">
                ← prev
            </a>
            <?php endif; ?>
            <?php if ($currentPage < $pages): ?>
            <a href="?page=<?= $currentPage + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . $status : '' ?>"
               class="font-mono text-[11px] text-muted no-underline px-2.5 py-1 border border-border rounded hover:border-border-md transition-colors">
                next →
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>