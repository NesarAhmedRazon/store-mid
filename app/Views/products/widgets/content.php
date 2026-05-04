<?php
$data = $data ?? [];

log_message('info','\Views\products\widgets\content.php');
log_message('debug',print_r($data,true));
?>
    <?= $data['js'];?>
<style>#product-description-wrap {<?= $data['css'];?>}</style>
<div class="dynamic-content-wrapper" id="product-description-wrap">
    <?= $data['html'];?>
    <!-- page content should be previewed here  -->
</div>