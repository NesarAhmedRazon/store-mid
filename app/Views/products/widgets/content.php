<?php 
$data = $data ?? [];

?>
<div class="dynamic-content-wrapper">
    <?php foreach ($data['normalized'] ?? [] as $element): 
        $type = $element['type']; // e.g., 'image'
        $props = $element['props'] ?? [];
        
        // Convert the props array into a string of HTML attributes
        $attributes = array_map(function($key, $value) {
            return sprintf('%s="%s"', esc($key), esc($value));
        }, array_keys($props), $props);
        
        $attrString = implode(' ', $attributes);
    ?>

        <div class="element-wrap" id="<?= esc($element['id']) ?>">
            <?php if ($type === 'image'): ?>
                <!-- Self-closing tags like <img> -->
                <img <?= $attrString ?> />
            <?php else: ?>
                <!-- Container tags like <div> or <section> -->
                <<?= $type ?> <?= $attrString ?>>
                    <?= $element['content'] ?? '' ?>
                </<?= $type ?>>
            <?php endif; ?>
        </div>

    <?php endforeach; ?>
</div>