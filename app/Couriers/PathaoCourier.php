<?php
/*
* directory: app/Couriers/PathaoCourier.php
* description: Handles Pathao webhook processing in a self-contained class.
*/

namespace App\Couriers;

use App\Models\ShipmentModel;
use App\Models\ShipmentEventModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Database\Exceptions\DatabaseException;

class PathaoCourier
{
    protected ShipmentModel $shipmentModel;
    protected ShipmentEventModel $eventModel;

    public function __construct()
    {
        $this->shipmentModel = new ShipmentModel();
        $this->eventModel = new ShipmentEventModel();
    }

    /**
     * Process Pathao webhook
     *
     * @param array $data JSON decoded webhook payload
     * @param string $authToken Auth token from DB
     * @param string $webhookSecret Secret from DB
     * @param IncomingRequest $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handle(array $data, string $authToken, string $webhookSecret, IncomingRequest $request, ResponseInterface $response): ResponseInterface
    {
        $db = db_connect();

        // Store raw webhook payload
        try {
            $db->table('courier_webhooks')->insert([
                'provider' => 'pathao',
                'consignment_id' => $data['consignment_id'] ?? null,
                'order_id' => $data['merchant_order_id'] ?? null,
                'payload' => json_encode($data),
                'headers' => json_encode($request->getHeaders()),
            ]);
        } catch (DatabaseException $e) {
            log_message('error', 'Failed to store raw Pathao webhook: ' . $e->getMessage());
        }

        // Validate Auth token / signature
        $incomingSignature = $request->getHeaderLine('X-PATHAO-Signature');
        if ($authToken && $incomingSignature !== $authToken) {
            return $response->setStatusCode(401)->setBody('Unauthorized');
        }

        // Idempotency check
        $hash = md5(json_encode($data));
        $existingEvent = $this->eventModel->where('event_hash', $hash)->first();
        if ($existingEvent) {
            // Already processed
            return $response->setStatusCode(202)
                ->setHeader('X-Pathao-Merchant-Webhook-Integration-Secret', $webhookSecret)
                ->setBody('Duplicate event ignored');
        }

        $eventStatus = str_replace('order.', '', $data['event'] ?? 'tracking_update');

        // DB transaction for shipment + event
        $db->transStart();

        // Insert or update shipment
        $shipment = $this->shipmentModel->where('consignment_id', $data['consignment_id'])->first();

        if (!$shipment) {
            $shipmentId = $this->shipmentModel->insert([
                'provider' => 'pathao',
                'consignment_id' => $data['consignment_id'] ?? null,
                'order_id' => $data['merchant_order_id'] ?? null,
                'delivery_fee' => $data['delivery_fee'] ?? null,
                'current_status' => $eventStatus,
                'created_at' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
        } else {
            $shipmentId = $shipment['id'];
            $this->shipmentModel->update($shipmentId, [
                'current_status' => $eventStatus,
                'updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
        }

        // Insert shipment event
        $this->eventModel->insert([
            'shipment_id' => $shipmentId,
            'provider_event' => $data['event'] ?? 'tracking_update',
            'normalized_status' => $eventStatus,
            'message' => $data['tracking_message'] ?? null,
            'event_time' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
            'event_hash' => $hash
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', 'Pathao webhook transaction failed for consignment_id: ' . ($data['consignment_id'] ?? 'unknown'));
            return $response->setStatusCode(500)->setBody('Internal Server Error');
        }

        return $response->setStatusCode(202)
            ->setHeader('X-Pathao-Merchant-Webhook-Integration-Secret', $webhookSecret)
            ->setBody('Webhook received and processed: ' . $eventStatus);
    }
}