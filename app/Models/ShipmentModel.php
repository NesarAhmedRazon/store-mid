<?php

namespace App\Models;

use CodeIgniter\Model;

class ShipmentModel extends Model
{
    protected $table = 'shipments';

    protected $allowedFields = [
        'provider',
        'consignment_id',
        'merchant_order_id',
        'invoice',
        'current_status',
        'cod_amount',
        'delivery_fee',
        'created_at',
        'updated_at'
    ];
}
