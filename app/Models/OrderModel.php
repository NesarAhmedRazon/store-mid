<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'wc_order_id',
        'wc_products',
        'wc_total'
    ];

    protected $useTimestamps = true;
}