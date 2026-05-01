<?php
$tabs = $data ?? [];
?>

<div class="card">

    <!-- Tab nav -->
    <div class="flex border-b border-border overflow-x-auto">
        <?php foreach ($tabs as $i => $tab): ?>
        <button onclick="openTab(event, '<?= esc($tab['tabId']) ?>')" class="tab-btn shrink-0 px-4 py-3 text-[11px] font-medium uppercase tracking-widest border-b-2 transition-colors duration-150
                   <?= $i === 0
                        ? 'border-text text-text'
                        : 'border-transparent text-subtle hover:text-muted hover:border-border-md' ?>">
            <?= esc($tab['tabLabel']) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Tab panels -->
    <div id="tab-container">
        <?php foreach ($tabs as $i => $tab): ?>
        <div id="<?= esc($tab['tabId']) ?>" class="tab-content <?= $i !== 0 ? 'hidden' : '' ?>">
            <?= view($tab['view'], ['data' => $tab['data']], ['saveData' => false]) ?>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
(function() {
    function openTab(event, tabId) {
        // Hide all panels
        document.querySelectorAll('.tab-content').forEach(function(el) {
            el.classList.add('hidden');
        });

        // Reset all buttons
        document.querySelectorAll('.tab-btn').forEach(function(btn) {
            btn.classList.remove('border-text', 'text-text');
            btn.classList.add('border-transparent', 'text-subtle');
        });

        // Show target panel
        var target = document.getElementById(tabId);
        if (target) target.classList.remove('hidden');

        // Activate clicked button
        event.currentTarget.classList.remove('border-transparent', 'text-subtle');
        event.currentTarget.classList.add('border-text', 'text-text');
    }

    // Expose globally so onclick="" works
    window.openTab = openTab;
}());
</script>