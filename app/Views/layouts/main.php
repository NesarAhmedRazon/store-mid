<!DOCTYPE html>
<html lang="en">
<?= $this->include('partials/head') ?>
<body class="bg-bg text-text min-h-screen">

<div class="flex min-h-screen">
    <?= $this->include('partials/sidebar') ?>
    <div class="flex-1 flex flex-col lg:ml-[220px] mt-[54px] lg:mt-0">
        <?= $this->include('partials/topbar') ?>
        <div class="p-4 lg:p-7">
            <?= $this->renderSection('content') ?>
        </div>
    </div>
</div>

</body>
</html>