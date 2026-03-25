<?php 
    $requestLog = $data ?? [];
?>
<div class="card <?= $class ?? '' ?>" > 
    <div class="card-head">
        <span class="card-title">Request log</span>
        <span class="placeholder-note">placeholder data</span>
    </div>
    <table class="w-full border-collapse">
        <thead>
            <tr class="bg-bg">
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border w-[60px]">Method</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border">Path</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border w-[60px]">Status</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border w-[120px]">Source</th>
                <th class="text-[10px] uppercase tracking-widest text-subtle font-medium text-left px-4 py-2.5 border-b border-border w-[100px]">Time</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $methodClass = ['GET' => 'bg-up-bg text-up', 'POST' => 'bg-info-bg text-info'];
            $statusClass = fn($s) => str_starts_with($s, '2') ? 'text-up' : (str_starts_with($s, '4') ? 'text-warn' : 'text-down');
            foreach ($requestLog as $log): ?>
            <tr class="hover:bg-bg transition-colors border-b border-border last:border-0">
                <td class="px-4 py-2.5">
                    <span class="font-mono text-[10px] font-medium px-1.5 py-0.5 rounded-sm <?= $methodClass[$log['method']] ?? 'bg-warn-bg text-warn' ?>">
                        <?= $log['method'] ?>
                    </span>
                </td>
                <td class="px-4 py-2.5 font-mono text-[12px] text-text">
                    <?= $log['path'] ?><span class="text-subtle"><?= $log['qs'] ?></span>
                </td>
                <td class="px-4 py-2.5 font-mono text-[12px] <?= $statusClass($log['status']) ?>">
                    <?= $log['status'] ?>
                </td>
                <td class="px-4 py-2.5 text-[11px] text-subtle"><?= $log['source'] ?></td>
                <td class="px-4 py-2.5 font-mono text-[11px] text-subtle"><?= $log['time'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>