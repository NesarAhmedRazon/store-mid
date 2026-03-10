<?php
/*
* directory: app/Controllers/Shipments.php
* description: Provides an API endpoint to retrieve shipment details and timeline based on order_id. This is used by the frontend to display shipment status to customers.
*/

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ShipmentEventModel;

class Shipments extends ResourceController
{
    protected $eventModel;

    public function __construct()
    {
        $this->eventModel = new ShipmentEventModel();
    }

    public function getStatusByOrder($orderId)
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

        $status = $shipment->current_status;
        $consignment = $shipment->consignment_id;
        $time = $shipment->created_at;

        return $this->respond([
            'order_id' => $orderId,
            'status' => $status,
            'consignment' => $consignment,
            'updated_at' => $time
        ]);
    }
    
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
    /**
     * GET /shipments
     * List shipments with optional filters
     */
    public function list()
    {
        $db = db_connect();

        $limit = $this->request->getGet('limit') ?? 50;
        $offset = $this->request->getGet('offset') ?? 0;

        $builder = $db->table('shipments');

        if ($status = $this->request->getGet('status')) {
            $builder->where('current_status', $status);
        }

        if ($provider = $this->request->getGet('provider')) {
            $builder->where('provider', $provider);
        }

        $shipments = $builder
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResult();

        return $this->respond([
            'count' => count($shipments),
            'shipments' => $shipments
        ]);
    }


    /**
     * GET /shipments/{order_id}/events
     * Retrieve only timeline events
     */
    public function events($orderId)
    {
        $db = db_connect();

        $shipment = $db->table('shipments')
            ->where('order_id', $orderId)
            ->get()
            ->getRow();

        if (!$shipment) {
            return $this->failNotFound('Shipment not found');
        }

        $timeline = $this->eventModel->getTimeline($shipment->id);

        return $this->respond([
            'order_id' => $orderId,
            'timeline' => $timeline
        ]);
    }
}
