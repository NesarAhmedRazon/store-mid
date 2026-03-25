<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Metric cards -->
<div class="grid grid-cols-4 gap-3 mb-6">
    <div class="metric metric-info">
        <div class="text-[10px] uppercase tracking-widest text-subtle mb-2">Total endpoints</div>
        <div class="text-[28px] font-light font-mono tracking-tight text-text">—</div>
        <div class="text-[11px] text-subtle mt-1.5">registered in system</div>
    </div>
    <div class="metric metric-up">
        <div class="text-[10px] uppercase tracking-widest text-subtle mb-2">Endpoints up</div>
        <div class="text-[28px] font-light font-mono tracking-tight text-up">—</div>
        <div class="text-[11px] text-subtle mt-1.5">last checked now</div>
    </div>
    <div class="metric metric-warn">
        <div class="text-[10px] uppercase tracking-widest text-subtle mb-2">Hooks today</div>
        <div class="text-[28px] font-light font-mono tracking-tight text-text">—</div>
        <div class="text-[11px] text-subtle mt-1.5">fired since midnight</div>
    </div>
    <div class="metric metric-down">
        <div class="text-[10px] uppercase tracking-widest text-subtle mb-2">Errors (24h)</div>
        <div class="text-[28px] font-light font-mono tracking-tight text-down">—</div>
        <div class="text-[11px] text-subtle mt-1.5">4xx + 5xx combined</div>
    </div>
</div>

<!-- data cards -->

<div class="grid grid-cols-3 gap-4 mb-4">
    <?php
            $endpoints = [
                ['method' => 'GET',  'path' => '/api/v1/orders',   'status' => 'up',   'badge' => 'up',   'latency' => '~120ms'],
                ['method' => 'GET',  'path' => '/api/v1/products', 'status' => 'up',   'badge' => 'up',   'latency' => '~98ms'],
                ['method' => 'POST', 'path' => '/api/v1/sync',     'status' => 'warn', 'badge' => 'warn', 'latency' => '~340ms'],
                ['method' => 'POST', 'path' => '/api/v1/webhook',  'status' => 'down', 'badge' => 'down', 'latency' => 'timeout'],
            ];
    ?>
    <!-- Products -->
     <?= view('dashboard/cards/products', ['data' => $products ?? [],'class'=>'col-span-2'],['saveData' => false]) ?> 
     <!-- Endpoint status -->
     <?= view('dashboard/cards/endpoints', ['data' => $endpoints ?? []],['saveData' => false]) ?>   

</div>
<!-- Endpoint status + Hook activity -->
<div class="grid grid-cols-4 gap-4 mb-4">
    <?php 
    $hooksLog = [
                ['name' => 'order.created',     'status' => 'up',   'code' => '200', 'time' => '2 min ago'],
                ['name' => 'stock.updated',     'status' => 'up',   'code' => '200', 'time' => '15 min ago'],
                ['name' => 'payment.confirmed', 'status' => 'down', 'code' => '500', 'time' => '1 hr ago'],
                ['name' => 'order.shipped',     'status' => 'up',   'code' => '200', 'time' => '3 hr ago'],
            ];
    
    $reqLog = [
                ['method' => 'GET',  'path' => '/api/v1/orders',          'qs' => '?limit=20', 'status' => '200', 'source' => '192.168.1.10', 'time' => 'just now'],
                ['method' => 'POST', 'path' => '/api/v1/sync',            'qs' => '',          'status' => '201', 'source' => '10.0.0.4',     'time' => '2 min ago'],
                ['method' => 'POST', 'path' => '/api/v1/webhook/payment', 'qs' => '',          'status' => '500', 'source' => 'stripe.com',    'time' => '1 hr ago'],
                ['method' => 'GET',  'path' => '/api/v1/products',        'qs' => '?category=smd', 'status' => '200', 'source' => '192.168.1.22', 'time' => '1 hr ago'],
                ['method' => 'GET',  'path' => '/api/v1/orders/9982',     'qs' => '',          'status' => '404', 'source' => '10.0.0.7',     'time' => '2 hr ago'],
            ];
    ?>
    
    <!-- Hook activity -->
    <?= view('dashboard/cards/hooksLog', ['data' => $hooksLog ?? []],['saveData' => false]) ?>  
    <!-- Request log -->
    <?= view('dashboard/cards/requestLog', ['data' => $reqLog ?? [],'class'=>'col-span-2'],['saveData' => false]) ?>  
</div>

 

<?= $this->endSection() ?>