<aside class="w-[220px] shrink-0 bg-surface border-r border-border flex flex-col fixed top-0 left-0 bottom-0">

    <!-- Brand -->
    <div class="px-5 py-4 border-b border-border">
        <div class="font-mono text-[13px] font-medium tracking-wide text-text">SMDPicker</div>
        <div class="text-[10px] text-subtle uppercase tracking-widest mt-0.5">Middleware Monitor</div>
    </div>

    <!-- Nav -->
    <nav class="p-2.5 flex-1">
        <div class="text-[10px] uppercase tracking-widest text-subtle px-2.5 pt-2 pb-1">Monitor</div>

        <a href="/dashboard" class="nav-item <?= uri_string() === 'dashboard' ? 'active' : '' ?>">
            <svg class="shrink-0 w-3.5 h-3.5 opacity-70" viewBox="0 0 16 16" fill="none"><rect x="1" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/><rect x="1" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/></svg>
            Dashboard
        </a>
        <a href="#" class="nav-item">
            <svg class="shrink-0 w-3.5 h-3.5 opacity-70" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.4"/><path d="M8 4v4l3 2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
            Request log
        </a>
        <a href="#" class="nav-item">
            <svg class="shrink-0 w-3.5 h-3.5 opacity-70" viewBox="0 0 16 16" fill="none"><path d="M2 11l4-4 3 3 5-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Endpoints
        </a>
        <a href="#" class="nav-item">
            <svg class="shrink-0 w-3.5 h-3.5 opacity-70" viewBox="0 0 16 16" fill="none"><path d="M14 3H2v2h12V3zM14 7H2v2h12V7zM8 11H2v2h6v-2z" fill="currentColor" opacity=".6"/></svg>
            Hooks
        </a>
        <a href="/products" class="nav-item">
            <svg class="shrink-0 w-3.5 h-3.5 opacity-70" viewBox="0 0 16 16" fill="none"><rect x="1.5" y="1.5" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.4"/><rect x="9.5" y="1.5" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.4"/><rect x="1.5" y="9.5" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.4"/><path d="M9.5 12h5M12 9.5v5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
            Products
        </a>

        <?php if (session()->get('role') === 'admin'): ?>
        <div class="text-[10px] uppercase tracking-widest text-subtle px-2.5 pt-4 pb-1">Admin</div>
        <a href="#" class="nav-item">
            <svg class="shrink-0 w-3.5 h-3.5 opacity-70" viewBox="0 0 16 16" fill="none"><circle cx="6" cy="5" r="3" stroke="currentColor" stroke-width="1.4"/><path d="M1 13c0-2.761 2.239-5 5-5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M11 9v6M8 12h6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
            Users
        </a>
        <?php endif; ?>
    </nav>

    <!-- Footer -->
    <div class="px-5 py-3.5 border-t border-border flex items-center gap-2.5">
        <div class="w-[30px] h-[30px] rounded-full bg-text text-white text-[11px] font-medium font-mono flex items-center justify-center shrink-0">
            <?= strtoupper(substr(session()->get('name') ?? 'U', 0, 2)) ?>
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-[12px] font-medium text-text truncate"><?= esc(session()->get('name')) ?></div>
            <div class="text-[10px] text-subtle uppercase tracking-wide"><?= esc(session()->get('role')) ?></div>
        </div>
        <a href="/logout" class="text-[11px] text-subtle no-underline px-2 py-1 rounded border border-border hover:border-border-md hover:text-muted transition-colors whitespace-nowrap">
            out
        </a>
    </div>

</aside>