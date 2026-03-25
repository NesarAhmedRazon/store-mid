<?php 
    $endpoints = $data ?? [];
?>
<div class="card <?= $class ?? '' ?>" >
        <div class="card-head">
            <span class="card-title">API endpoint status</span>
            <span class="placeholder-note">placeholder data</span>
        </div>
        <div>
            <?php            
            $dotColor = ['up' => 'bg-up', 'warn' => 'bg-[#D4850A]', 'down' => 'bg-down'];
            foreach ($endpoints as $ep): ?>
            <div class="flex items-center gap-3 px-4 py-2.5 border-b border-border last:border-0 hover:bg-bg transition-colors">
                <div class="w-[7px] h-[7px] rounded-full shrink-0 <?= $dotColor[$ep['status']] ?>"></div>
                <span class="font-mono text-[10px] text-subtle w-8"><?= $ep['method'] ?></span>
                <span class="font-mono text-[12px] text-text flex-1"><?= $ep['path'] ?></span>
                <span class="badge badge-<?= $ep['badge'] ?>"><?= $ep['status'] ?></span>
                <span class="font-mono text-[11px] text-subtle min-w-[52px] text-right"><?= $ep['latency'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>