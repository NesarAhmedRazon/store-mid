<?php
/*
* directory: app/Models/ShipmentModel.php
* description: Model representing the 'shipments' table in the database. It defines the structure of shipment records and allows for easy interaction with shipment data using CodeIgniter's Model features.
*/

namespace App\Models;

use CodeIgniter\Model;

class ShipmentModel extends Model
{
    protected $table = 'shipments';

    protected $allowedFields = [
        'provider',
        'consignment_id',
        'order_id',
        'invoice',
        'current_status',
        'cod_amount',
        'delivery_fee',
        'created_at',
        'updated_at'
    ];
}
