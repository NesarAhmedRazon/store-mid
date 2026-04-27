<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$statusBadge = ['active' => 'up', 'inactive' => 'warn', 'banned' => 'down'];
$sourceBadge = ['wp_import' => 'info', 'google' => 'info', 'facebook' => 'info', 'manual' => 'warn'];
$isSocial    = in_array($customer->source, ['google', 'facebook']);
$billing     = $customer->billing_address ? json_decode($customer->billing_address, true) : null;
?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-[12px] font-mono text-subtle mb-5">
    <a href="/customers" class="hover:text-muted transition-colors no-underline">Customers</a>
    <span>/</span>
    <span class="text-text"><?= esc($customer->name) ?></span>
</div>

<!-- Flash -->
<?php if (session()->getFlashdata('success')): ?>
<div class="flex items-center gap-2 bg-up-bg border border-up/20 text-up text-[13px] px-4 py-3 rounded-lg mb-5">
    <?= esc(session()->getFlashdata('success')) ?>
</div>
<?php endif; ?>

<!-- Action bar -->
<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-2">
        <span class="badge badge-<?= $statusBadge[$customer->status] ?>"><?= $customer->status ?></span>
        <span class="badge badge-<?= $sourceBadge[$customer->source] ?>"><?= $customer->source ?></span>
    </div>
    <div class="flex items-center gap-2">
        <a href="/customers/<?= $customer->id ?>/edit"
           class="text-[11px] font-mono text-text no-underline px-2.5 py-1 border border-border-md rounded hover:bg-bg transition-colors">
            edit →
        </a>
        <a href="/customers/<?= $customer->id ?>/delete"
           class="text-[11px] font-mono text-down no-underline px-2.5 py-1 border border-down/30 rounded hover:border-down/60 transition-colors"
           onclick="return confirm('Delete <?= esc($customer->name) ?>? All their tokens will be revoked. This cannot be undone.')">
            delete
        </a>
    </div>
</div>

<div class="grid grid-cols-3 gap-4">

    <!-- ── Left (2/3) ── -->
    <div class="col-span-2 flex flex-col gap-4">

        <!-- Core info -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Customer info</span>
            </div>
            <div class="p-5 flex flex-col gap-4">
                <div class="flex items-center gap-4">
                    <?php if ($customer->avatar_url): ?>
                    <img src="<?= esc($customer->avatar_url) ?>" class="w-14 h-14 rounded-full object-cover border border-border shrink-0" alt="">
                    <?php else: ?>
                    <div class="w-14 h-14 rounded-full bg-text text-white text-[18px] font-mono font-medium flex items-center justify-center shrink-0">
                        <?= strtoupper(substr($customer->name, 0, 2)) ?>
                    </div>
                    <?php endif; ?>
                    <div>
                        <div class="text-[16px] font-medium text-text"><?= esc($customer->name) ?></div>
                        <div class="font-mono text-[13px] text-muted mt-0.5"><?= esc($customer->email) ?></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 pt-2 border-t border-border">
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Phone</div>
                        <div class="font-mono text-[13px] text-text"><?= $customer->phone ? esc($customer->phone) : '—' ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Source</div>
                        <span class="badge badge-<?= $sourceBadge[$customer->source] ?>"><?= $customer->source ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Billing address -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Billing address</span>
            </div>
            <div class="p-5">
                <?php if ($billing): ?>
                <div class="grid grid-cols-2 gap-4">
                    <?php foreach ([
                        'line1'    => 'Address line 1',
                        'line2'    => 'Address line 2',
                        'city'     => 'City',
                        'state'    => 'State / Division',
                        'postcode' => 'Postcode',
                        'country'  => 'Country',
                    ] as $key => $label): ?>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-subtle mb-1"><?= $label ?></div>
                        <div class="font-mono text-[13px] text-text"><?= !empty($billing[$key]) ? esc($billing[$key]) : '—' ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-[13px] font-mono text-subtle">No billing address on record.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ── Right (1/3) ── -->
    <div class="flex flex-col gap-4">

        <!-- Status -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Account</span>
            </div>
            <div class="p-5 flex flex-col gap-4">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Status</div>
                    <span class="badge badge-<?= $statusBadge[$customer->status] ?>"><?= $customer->status ?></span>
                </div>
                <?php if ($customer->wp_user_id): ?>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">WP user ID</div>
                    <div class="font-mono text-[13px] text-text">#<?= esc($customer->wp_user_id) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($isSocial): ?>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">
                        <?= $customer->source === 'google' ? 'Google ID' : 'Facebook ID' ?>
                    </div>
                    <div class="font-mono text-[11px] text-muted break-all">
                        <?= esc($customer->source === 'google' ? $customer->google_id : $customer->facebook_id) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timestamps -->
        <div class="card">
            <div class="card-head">
                <span class="card-title">Timestamps</span>
            </div>
            <div class="p-5 flex flex-col gap-4">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Last login</div>
                    <div class="font-mono text-[12px] text-text">
                        <?= $customer->last_login_at ? date('d M Y, H:i', strtotime($customer->last_login_at)) : '—' ?>
                    </div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Joined</div>
                    <div class="font-mono text-[12px] text-text">
                        <?= $customer->created_at ? date('d M Y, H:i', strtotime($customer->created_at)) : '—' ?>
                    </div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Last updated</div>
                    <div class="font-mono text-[12px] text-text">
                        <?= $customer->updated_at ? date('d M Y, H:i', strtotime($customer->updated_at)) : '—' ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?= $this->endSection() ?>
