<?php

namespace App\Models;

use CodeIgniter\Model;

class ShipmentEventModel extends Model
{
    protected $table = 'shipment_events';

    protected $allowedFields = [
        'shipment_id',
        'provider_event',
        'normalized_status',
        'message',
        'event_time'
    ];
}
