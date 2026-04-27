<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isSocial = in_array($customer->source, ['google', 'facebook']);
$billing  = $customer->billing_address ? json_decode($customer->billing_address, true) : [];
$statusBadge = ['active' => 'up', 'inactive' => 'warn', 'banned' => 'down'];
?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-[12px] font-mono text-subtle mb-5">
    <a href="/customers" class="hover:text-muted transition-colors no-underline">Customers</a>
    <span>/</span>
    <a href="/customers/<?= $customer->id ?>" class="hover:text-muted transition-colors no-underline"><?= esc($customer->name) ?></a>
    <span>/</span>
    <span class="text-text">Edit</span>
</div>

<?php if (!empty($errors)): ?>
<div class="bg-down-bg border border-down/20 text-down text-[13px] px-4 py-3 rounded-lg mb-5">
    <?php foreach ($errors as $e): ?>
    <div><?= esc($e) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($isSocial): ?>
<div class="flex items-center gap-2.5 bg-info-bg border border-info/20 text-info text-[12px] font-mono px-4 py-3 rounded-lg mb-5">
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.4"/><path d="M8 7v4M8 5h.01" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    This customer signed in via <?= ucfirst($customer->source) ?>. Email is read-only to prevent login mismatch.
</div>
<?php endif; ?>

<?= form_open('/customers/' . $customer->id) ?>

<div class="grid grid-cols-3 gap-4">

    <!-- ── Left (2/3) ── -->
    <div class="col-span-2 flex flex-col gap-4">

        <!-- Core info -->
        <div class="card">
            <div class="card-head"><span class="card-title">Customer info</span></div>
            <div class="p-5 flex flex-col gap-4">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">
                            Full name <span class="text-down">*</span>
                        </label>
                        <input type="text" name="name" value="<?= esc($customer->name) ?>"
                               class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md"
                               required>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">Email</label>
                        <?php if ($isSocial): ?>
                        <!-- Read-only for social login customers -->
                        <div class="w-full font-mono text-[13px] text-subtle bg-bg border border-border rounded-md px-3 py-2 cursor-not-allowed">
                            <?= esc($customer->email) ?>
                        </div>
                        <input type="hidden" name="email" value="<?= esc($customer->email) ?>">
                        <?php else: ?>
                        <input type="email" name="email" value="<?= esc($customer->email) ?>"
                               class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md"
                               required>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">Phone</label>
                    <input type="text" name="phone" value="<?= esc($customer->phone ?? '') ?>"
                           class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md"
                           placeholder="+880 1xxx-xxxxxx">
                </div>

                <!-- Social IDs — read only, shown for info -->
                <?php if ($isSocial): ?>
                <div class="pt-2 border-t border-border">
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1.5">
                        <?= $customer->source === 'google' ? 'Google ID' : 'Facebook ID' ?>
                        <span class="normal-case tracking-normal ml-1 text-subtle/60">(read-only)</span>
                    </div>
                    <div class="font-mono text-[11px] text-subtle break-all">
                        <?= esc($customer->source === 'google' ? $customer->google_id : $customer->facebook_id) ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Billing address -->
        <div class="card">
            <div class="card-head"><span class="card-title">Billing address</span></div>
            <div class="p-5 grid grid-cols-2 gap-4">
                <?php
                $billingFields = [
                    'line1'    => ['label' => 'Address line 1',  'placeholder' => '123 Main St',      'span' => 2],
                    'line2'    => ['label' => 'Address line 2',  'placeholder' => 'Apt, suite, etc.', 'span' => 2],
                    'city'     => ['label' => 'City',            'placeholder' => 'Dhaka',             'span' => 1],
                    'state'    => ['label' => 'State / Division','placeholder' => 'Dhaka Division',    'span' => 1],
                    'postcode' => ['label' => 'Postcode',        'placeholder' => '1212',              'span' => 1],
                    'country'  => ['label' => 'Country',         'placeholder' => 'Bangladesh',        'span' => 1],
                ];
                foreach ($billingFields as $key => $field):
                ?>
                <div class="<?= $field['span'] === 2 ? 'col-span-2' : '' ?>">
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5"><?= $field['label'] ?></label>
                    <input type="text" name="billing_address[<?= $key ?>]"
                           value="<?= esc($billing[$key] ?? '') ?>"
                           class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md"
                           placeholder="<?= $field['placeholder'] ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- ── Right (1/3) ── -->
    <div class="flex flex-col gap-4">

        <!-- Actions -->
        <div class="card">
            <div class="card-head"><span class="card-title">Save</span></div>
            <div class="p-4 flex flex-col gap-3">
                <button type="submit"
                        class="w-full font-mono text-[13px] text-surface bg-text rounded-md px-3 py-2 cursor-pointer hover:opacity-80 transition-opacity border-none">
                    save changes
                </button>
                <a href="/customers/<?= $customer->id ?>"
                   class="block text-center font-mono text-[12px] text-subtle no-underline hover:text-muted transition-colors">
                    cancel
                </a>
            </div>
        </div>

        <!-- Status -->
        <div class="card">
            <div class="card-head"><span class="card-title">Status</span></div>
            <div class="p-4">
                <select name="status"
                        class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md">
                    <option value="active"   <?= $customer->status === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $customer->status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="banned"   <?= $customer->status === 'banned'   ? 'selected' : '' ?>>Banned</option>
                </select>
                <?php if ($isSocial): ?>
                <p class="text-[10px] text-subtle mt-2 font-mono">
                    Setting to <span class="text-down">banned</span> will revoke all active tokens.
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Account info — read only -->
        <div class="card">
            <div class="card-head"><span class="card-title">Account</span></div>
            <div class="p-5 flex flex-col gap-3">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Source</div>
                    <span class="badge badge-info"><?= $customer->source ?></span>
                </div>
                <?php if ($customer->wp_user_id): ?>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">WP user ID</div>
                    <div class="font-mono text-[13px] text-text">#<?= esc($customer->wp_user_id) ?></div>
                </div>
                <?php endif; ?>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Joined</div>
                    <div class="font-mono text-[12px] text-text">
                        <?= $customer->created_at ? date('d M Y', strtotime($customer->created_at)) : '—' ?>
                    </div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-subtle mb-1">Last login</div>
                    <div class="font-mono text-[12px] text-text">
                        <?= $customer->last_login_at ? date('d M Y, H:i', strtotime($customer->last_login_at)) : '—' ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?= form_close() ?>

<?= $this->endSection() ?>
