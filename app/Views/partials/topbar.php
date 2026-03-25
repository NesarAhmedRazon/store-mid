<header class="bg-surface border-b border-border px-7 h-[54px] flex items-center justify-between sticky top-0 z-10">

    <div class="flex items-center">
        <h1 class="text-[14px] font-medium text-text"><?= $title ?? 'Dashboard' ?></h1>
        <span class="text-[12px] text-subtle ml-2.5 font-mono"><?= date('D, d M Y') ?></span>
    </div>

    <div class="flex items-center gap-2.5">
        <div class="flex items-center gap-1.5 text-[11px] text-up bg-up-bg px-2.5 py-1 rounded-full font-mono">
            <span class="w-[7px] h-[7px] rounded-full bg-up animate-pulse"></span>
            system nominal
        </div>
        <button
            onclick="window.location.reload()"
            class="flex items-center gap-1.5 text-[12px] font-mono text-muted bg-transparent border border-border rounded-md px-3 py-1 cursor-pointer hover:border-border-md hover:text-text transition-colors"
        >
            <svg width="12" height="12" viewBox="0 0 16 16" fill="none"><path d="M13.5 8A5.5 5.5 0 1 1 8 2.5c1.8 0 3.4.87 4.4 2.2L14 3v4h-4l1.6-1.6A4 4 0 1 0 12 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
            refresh
        </button>
    </div>

</header>