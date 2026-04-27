<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-[12px] font-mono text-subtle mb-5">
    <a href="/customers" class="hover:text-muted transition-colors no-underline">Customers</a>
    <span>/</span>
    <span class="text-text">New customer</span>
</div>

<?php if (!empty($errors)): ?>
<div class="bg-down-bg border border-down/20 text-down text-[13px] px-4 py-3 rounded-lg mb-5">
    <?php foreach ($errors as $e): ?>
    <div><?= esc($e) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?= form_open('/customers') ?>

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
                        <input type="text" name="name" value="<?= esc($old['name'] ?? '') ?>"
                               class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md"
                               placeholder="John Doe" required>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">
                            Email <span class="text-down">*</span>
                        </label>
                        <input type="email" name="email" value="<?= esc($old['email'] ?? '') ?>"
                               class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md"
                               placeholder="you@example.com" required>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5">Phone</label>
                    <input type="text" name="phone" value="<?= esc($old['phone'] ?? '') ?>"
                           class="w-full font-mono text-[13px] text-text bg-bg border border-border rounded-md px-3 py-2 outline-none focus:border-border-md"
                           placeholder="+880 1xxx-xxxxxx">
                </div>

            </div>
        </div>

        <!-- Billing address -->
        <div class="card">
            <div class="card-head"><span class="card-title">Billing address</span></div>
            <div class="p-5 grid grid-cols-2 gap-4">
                <?php
                $billingFields = [
                    'line1'    => ['label' => 'Address line 1', 'placeholder' => '123 Main St',     'span' => 2],
                    'line2'    => ['label' => 'Address line 2', 'placeholder' => 'Apt, suite, etc.','span' => 2],
                    'city'     => ['label' => 'City',           'placeholder' => 'Dhaka',            'span' => 1],
                    'state'    => ['label' => 'State / Division','placeholder' => 'Dhaka Division',  'span' => 1],
                    'postcode' => ['label' => 'Postcode',        'placeholder' => '1212',            'span' => 1],
                    'country'  => ['label' => 'Country',         'placeholder' => 'Bangladesh',      'span' => 1],
                ];
                foreach ($billingFields as $key => $field):
                ?>
                <div class="<?= $field['span'] === 2 ? 'col-span-2' : '' ?>">
                    <label class="block text-[10px] uppercase tracking-widest text-subtle mb-1.5"><?= $field['label'] ?></label>
                    <input type="text" name="billing_address[<?= $key ?>]"
                           value="<?= esc($old['billing_address'][$key] ?? '') ?>"
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
                    create customer
                </button>
                <a href="/customers"
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
                    <option value="active"   <?= ($old['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($old['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="banned"   <?= ($old['status'] ?? '') === 'banned'   ? 'selected' : '' ?>>Banned</option>
                </select>
                <p class="text-[10px] text-subtle mt-2 font-mono">Source will be set to <span class="text-muted">manual</span>.</p>
            </div>
        </div>

    </div>
</div>

<?= form_close() ?>

<?= $this->endSection() ?>
