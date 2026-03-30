 <?php
$attributes   = $data ?? [];

?>

        <div class="card">
            <div class="card-head">
                <span class="card-title">Attributes</span>
                <!-- <span class="placeholder-note">not implemented</span> -->
            </div>
            <table class="w-full border-collapse">
                <?php foreach ($attributes as $attr): ?>
                <tr class="border-b border-border last:border-0 hover:bg-bg transition-colors">
                    <td class="px-4 py-2.5 text-[10px] uppercase tracking-widest text-subtle font-medium w-44 whitespace-nowrap">
                        <?= esc($attr['name']) ?>
                    </td>
                    <td class="px-4 py-2.5 font-mono text-[12px] text-text">
                        <?= esc($attr['value']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>