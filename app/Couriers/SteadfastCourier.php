<?php

/*
* directory: app/Couriers/SteadfastCourier.php
* description: A dedicated class to handle Steadfast courier webhooks. It processes incoming data
* from Steadfast, performs idempotency checks, updates the shipment and event records in the database, and ensures proper authentication using a secret token.
*/

namespace App\Couriers;

use App\Models\ShipmentModel;
use App\Models\ShipmentEventModel;

class SteadfastCourier
{
    protected $shipmentModel;
    protected $eventModel;

    public function __construct()
    {
        $this->shipmentModel = new ShipmentModel();
        $this->eventModel = new ShipmentEventModel();
    }

    public function handle(array $data, string $authToken)
    {
        $shipmentModel = new ShipmentModel();
        $eventModel = new ShipmentEventModel();

        // Get Steadfast auth_token
        $provider = db_connect()->table('courier_providers')->where('name', 'steadfast')->get()->getRow();
        $authToken = $provider->auth_token ?? null;

        // Bearer token verification
        $incomingToken = $this->request->getHeaderLine('Authorization');
        if ($authToken && $incomingToken !== 'Bearer ' . $authToken) {
            return $this->fail('Unauthorized', 401);
        }

        // Idempotency check
        $hash = md5(json_encode($data));
        $existingEvent = $eventModel->where('event_hash', $hash)->first();
        if ($existingEvent) {
            return $this->respond(['status' => 'ok'], 200);
        }

        // Insert or update shipment
        $shipment = $shipmentModel->where('consignment_id', $data['consignment_id'])->first();
        if (!$shipment) {
            $shipmentId = $shipmentModel->insert([
                'provider'       => 'steadfast',
                'consignment_id' => $data['consignment_id'],
                'invoice'        => $data['invoice'] ?? null,
                'cod_amount'     => $data['cod_amount'] ?? null,
                'delivery_fee'   => $data['delivery_charge'] ?? null,
                'current_status' => $data['status'] ?? 'tracking_update',
                'created_at'     => $data['updated_at'] ?? date('Y-m-d H:i:s'),
                'updated_at'     => $data['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
        } else {
            $shipmentId = $shipment['id'];
            $shipmentModel->update($shipmentId, [
                'current_status' => $data['status'] ?? 'tracking_update',
                'updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
        }

        // Insert event
        $eventModel->insert([
            'shipment_id'       => $shipmentId,
            'provider_event'    => $data['status'] ?? $data['notification_type'],
            'normalized_status' => $data['status'] ?? 'tracking_update',
            'message'           => $data['tracking_message'] ?? null,
            'event_time'        => $data['updated_at'] ?? date('Y-m-d H:i:s'),
            'event_hash'        => $hash
        ]);

        return $this->respond(['status' => 'ok'], 200);
    }
}