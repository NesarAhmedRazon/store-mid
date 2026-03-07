<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Shipments extends ResourceController
{

    public function getByOrder($orderId)
    {
        $db = db_connect();

        $shipment = $db->table('shipments')
            ->where('merchant_order_id', $orderId)
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
