<?php

/*
* directory: app/Models/ShipmentEventModel.php
* description: This model represents the shipment events stored in the 'shipment_events' table. It defines the allowed fields for mass assignment.
*/

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
