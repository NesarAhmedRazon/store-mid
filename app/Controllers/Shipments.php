<?php
/*
* directory: app/Controllers/Shipments.php
* description: Provides an API endpoint to retrieve shipment details and timeline based on order_id. This is used by the frontend to display shipment status to customers.
*/

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Shipments extends ResourceController
{

    public function getByOrder($orderId)
    {
        $db = db_connect();

        $shipment = $db->table('shipments')
            ->where('order_id', $orderId)
            ->get()
            ->getRow();

        if (!$shipment) {
            return $this->respond([
                'order_id' => $orderId,
                'shipment' => null
            ]);
        }

        $events = $db->table('shipment_events')
            ->where('shipment_id', $shipment->id)
            ->orderBy('event_time', 'ASC')
            ->get()
            ->getResult();

        return $this->respond([
            'order_id' => $orderId,
            'shipment' => $shipment,
            'timeline' => $events
        ]);
    }
}
