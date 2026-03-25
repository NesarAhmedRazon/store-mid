<?php 
    $hooksLog = $data ?? [];
?>
<div class="card <?= $class ?? '' ?>" >
    <div class="card-head">
            <span class="card-title">Recent hook activity</span>
            <span class="placeholder-note">placeholder data</span>
        </div>
        <div>
            <?php            
            foreach ($hooksLog as $hook):
                $iconColor = $hook['status'] === 'up' ? 'var(--color-up)' : 'var(--color-down)';
                $iconBg    = $hook['status'] === 'up' ? 'bg-bg' : 'bg-down-bg';
                $icon      = $hook['status'] === 'up'
                    ? '<path d="M2 8l4 4 8-8" stroke="' . $iconColor . '" stroke-width="1.8" stroke-linecap="round"/>'
                    : '<path d="M4 4l8 8M12 4l-8 8" stroke="' . $iconColor . '" stroke-width="1.8" stroke-linecap="round"/>';
            ?>
            <div class="flex items-center gap-3 px-4 py-2.5 border-b border-border last:border-0 hover:bg-bg transition-colors">
                <div class="w-7 h-7 rounded-md <?= $iconBg ?> border border-border flex items-center justify-center shrink-0">
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="none"><?= $icon ?></svg>
                </div>
                <span class="font-mono text-[12px] text-text flex-1"><?= $hook['name'] ?></span>
                <span class="badge badge-<?= $hook['status'] ?>"><?= $hook['code'] ?></span>
                <span class="font-mono text-[11px] text-subtle min-w-[60px] text-right"><?= $hook['time'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>