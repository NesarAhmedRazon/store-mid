<?php
$data = $data ?? [];
?>

<!-- Tab Navigation -->
<div class="flex border-b border-border mb-6 gap-6">
    <?php foreach ($data as $key => $tab): ?>
        <button onclick="openTab(event, '<?= $tab['tabId'] ?>')" class="tab-link <?= $key === 0 ? 'border-primary text-text' : 'border-transparent text-subtle' ?> py-2 px-4 border-b-2 font-medium text-sm transition-all">
            <?= $tab['tabLabel'] ?>
        </button>
    <?php endforeach; ?>
</div>

<div id="tab-container">
    <?php foreach ($data as $key => $tab):  ?>

        <div id="<?= $tab['tabId'] ?>" class="tab-content <?= $key === 0 ? '' : 'hidden' ?>">
            <?= view($tab['view'], ['data' => $tab['data']]) ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
    function openTab(event, tabId) {
        // 1. Hide all tab content areas
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => {
            content.classList.add('hidden');
            content.classList.remove('block');
        });

        // 2. Reset all tab button styles
        const links = document.querySelectorAll('.tab-link');
        links.forEach(link => {
            link.classList.remove('border-primary', 'text-text');
            link.classList.add('border-transparent', 'text-subtle');
        });

        // 3. Show the specific tab content
        const target = document.getElementById(tabId);
        if (target) {
            target.classList.remove('hidden');
            target.classList.add('block');
        }

        // 4. Highlight the clicked button
        event.currentTarget.classList.remove('border-transparent', 'text-subtle');
        event.currentTarget.classList.add('border-primary', 'text-text');
    }
</script>