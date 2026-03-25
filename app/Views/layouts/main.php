<!DOCTYPE html>
<html lang="en">
<?= $this->include('partials/head') ?>
<body class="bg-bg text-text min-h-screen">

<div class="flex min-h-screen">
    <?= $this->include('partials/sidebar') ?>
    <div class="ml-[220px] flex-1 flex flex-col">
        <?= $this->include('partials/topbar') ?>
        <div class="p-7">
            <?= $this->renderSection('content') ?>
        </div>
    </div>
</div>

</body>
</html>