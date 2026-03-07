<?php

/*
* directory: app/Controllers/CourierWebhook.php
* description: Handles incoming webhook notifications from courier providers (Pathao and Steadfast)
*/

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ShipmentModel;
use App\Models\ShipmentEventModel;

class CourierWebhook extends ResourceController
{
    /**
     * Entry point for webhooks
     * $provider = 'pathao' or 'steadfast'
     */
    public function receive($provider)
    {
        $data = $this->request->getJSON(true);
        if (!$data) {
            return $this->fail('Invalid JSON', 400);
        }

        if ($provider === 'pathao') {
            return $this->handlePathao($data);
        }

        if ($provider === 'steadfast') {
            return $this->handleSteadfast($data);
        }

        return $this->fail('Unknown provider', 400);
    }

    /**
     * Handle Pathao webhook
     * Adds required X-Pathao-Merchant-Webhook-Integration-Secret header
     */
    private function handlePathao(array $data)
    {
        $shipmentModel = new ShipmentModel();
        $eventModel = new ShipmentEventModel();

        // Get Pathao auth_token/webhook_secret from DB
        $provider = db_connect()->table('courier_providers')->where('name', 'pathao')->get()->getRow();
        $webhookSecret = $provider->webhook_secret ?? null;
        $authToken     = $provider->auth_token ?? null;

        // Optional: verify Authorization header if set
        $incomingToken = $this->request->getHeaderLine('Authorization');
        if ($authToken && $incomingToken !== 'Bearer ' . $authToken) {
            return $this->fail('Unauthorized', 401);
        }

        // Idempotency check: skip if already exists
        $hash = md5(json_encode($data));
        $existingEvent = $eventModel->where('event_hash', $hash)->first();
        if ($existingEvent) {
            return $this->response->setStatusCode(200)
                ->setHeader('X-Pathao-Merchant-Webhook-Integration-Secret', $webhookSecret);
        }

        // Insert or update shipment
        $shipment = $shipmentModel->where('consignment_id', $data['consignment_id'])->first();
        if (!$shipment) {
            $shipmentId = $shipmentModel->insert([
                'provider'           => 'pathao',
                'consignment_id'     => $data['consignment_id'],
                'merchant_order_id'  => $data['merchant_order_id'] ?? null,
                'delivery_fee'       => $data['delivery_fee'] ?? null,
                'current_status'     => $data['event'],
                'created_at'         => $data['updated_at'] ?? date('Y-m-d H:i:s'),
                'updated_at'         => $data['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
        } else {
            $shipmentId = $shipment['id'];
            $shipmentModel->update($shipmentId, [
                'current_status' => $data['event'],
                'updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
        }

        // Insert event
        $eventModel->insert([
            'shipment_id'      => $shipmentId,
            'provider_event'   => $data['event'],
            'normalized_status' => $data['event'],
            'event_time'       => $data['updated_at'] ?? date('Y-m-d H:i:s'),
            'event_hash'       => $hash
        ]);

        // Respond with required Pathao header
        return $this->response->setStatusCode(200)
            ->setHeader('X-Pathao-Merchant-Webhook-Integration-Secret', $webhookSecret);
    }

    /**
     * Handle Steadfast webhook
     */
    private function handleSteadfast(array $data)
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
