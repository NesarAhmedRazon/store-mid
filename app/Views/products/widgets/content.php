<?php
$data = $data ?? [];


?>
    <?= isset($data['js']) ? $data['js']: '';?>
<style>#product-description-wrap {<?= isset($data['css'])?$data['css'] :'';?>}</style>
<div class="dynamic-content-wrapper" id="product-description-wrap">
    <?= isset($data['html'])?$data['html']:"";?>
    <!-- page content should be previewed here  -->
</div>