<?php

/*
 * directory: app/Couriers/SteadfastCourier.php
 * description: Handles Steadfast webhook processing in a self-contained class.
 *              Receives delivery_status and tracking_update notifications, persists
 *              shipment/event records, and forwards a Pathao-style normalized payload
 *              to the configured external endpoint via ExternalWebhook.
 */

namespace App\Couriers;

use App\Models\ShipmentModel;
use App\Models\ShipmentEventModel;
use App\Libraries\ExternalWebhook;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Database\Exceptions\DatabaseException;

class SteadfastCourier
{
    protected ShipmentModel $shipmentModel;
    protected ShipmentEventModel $eventModel;

    public function __construct()
    {
        $this->shipmentModel = new ShipmentModel();
        $this->eventModel    = new ShipmentEventModel();
    }

    /**
     * Map Steadfast data to our existing WooCommerce order event slugs.
     *
     * Two paths:
     *  A) delivery_status  → map the explicit $status field
     *  B) tracking_update  → keyword-match on $trackingMessage
     *
     * WC slug reference (wc- prefix stripped):
     *   pickup_requested | pickup_ok    | pickup_error   | pickup_updated
     *   at_sorting_hub   | on_the_way   | last_mile_hub
     *   ready_to_delivery| delivery_success | delivery_failed | delivery_hold
     *   returned
     *
     * @param string      $notificationType  "delivery_status" | "tracking_update"
     * @param string|null $status            Steadfast status field (delivery_status only)
     * @param string|null $trackingMessage   Free-text tracking message
     * @return string  Normalized event slug (used as order.{slug} in the forwarded payload)
     */
    private function resolveStatus(
        string $notificationType,
        ?string $status,
        ?string $trackingMessage
    ): string {
        // ------------------------------------------------------------------
        // Path A: delivery_status — explicit status field from Steadfast
        //   pending          → pickup_ok       (parcel collected, awaiting delivery)
        //   delivered        → delivery_success
        //   partial_delivered→ delivery_hold   (some items held / partially done)
        //   cancelled        → returned        (consignment cancelled / sent back)
        //   unknown          → delivery_failed (unresolved outcome)
        // ------------------------------------------------------------------
        if ($notificationType === 'delivery_status' && $status !== null) {
            $statusMap = [
                'pending'           => 'pickup_ok',
                'delivered'         => 'delivery_success',
                'partial_delivered' => 'delivery_hold',
                'cancelled'         => 'returned',
                'unknown'           => 'delivery_failed',
            ];
            return $statusMap[strtolower($status)] ?? 'delivery_failed';
        }

        // ------------------------------------------------------------------
        // Path B: tracking_update — keyword match on tracking_message text
        //
        // Order of checks matters: more specific phrases first.
        // ------------------------------------------------------------------
        if ($trackingMessage !== null) {
            $msg = strtolower($trackingMessage);

            // Delivery outcomes
            if (str_contains($msg, 'delivered successfully') || str_contains($msg, 'delivery successful')) {
                return 'delivery_success';
            }
            if (str_contains($msg, 'delivery failed') || str_contains($msg, 'failed to deliver')) {
                return 'delivery_failed';
            }
            if (str_contains($msg, 'delivery on hold') || str_contains($msg, 'hold')) {
                return 'delivery_hold';
            }

            // Return
            if (str_contains($msg, 'returned') || str_contains($msg, 'return to sender')) {
                return 'returned';
            }

            // Out for / assigned for delivery
            if (
                str_contains($msg, 'out for delivery') ||
                str_contains($msg, 'assigned for delivery') ||
                str_contains($msg, 'ready for delivery')
            ) {
                return 'ready_to_delivery';
            }

            // Hub stages
            if (str_contains($msg, 'last mile')) {
                return 'last_mile_hub';
            }
            if (str_contains($msg, 'sorting center') || str_contains($msg, 'sorting hub')) {
                return 'at_sorting_hub';
            }

            // Transit
            if (str_contains($msg, 'in transit') || str_contains($msg, 'on the way')) {
                return 'on_the_way';
            }

            // Pickup stages
            if (str_contains($msg, 'pickup requested') || str_contains($msg, 'pickup request')) {
                return 'pickup_requested';
            }
            if (str_contains($msg, 'pickup failed') || str_contains($msg, 'pickup error')) {
                return 'pickup_error';
            }
            if (str_contains($msg, 'picked up') || str_contains($msg, 'parcel picked')) {
                return 'pickup_ok';
            }
            if (str_contains($msg, 'shipping updated') || str_contains($msg, 'shipment updated')) {
                return 'pickup_updated';
            }
        }

        // Fallback — keeps a record without mapping to a wrong WC status
        return 'tracking_update';
    }

    /**
     * Process a Steadfast webhook notification.
     *
     * @param array             $data        JSON-decoded webhook body
     * @param string            $authToken   Bearer token stored in DB for this provider
     * @param IncomingRequest   $request     CI4 incoming request
     * @param ResponseInterface $response    CI4 response object
     * @return ResponseInterface
     */
    public function handle(
        array $data,
        string $authToken,
        IncomingRequest $request,
        ResponseInterface $response
    ): ResponseInterface {
        $db  = db_connect();
        $env = env('CI_ENVIRONMENT', 'production');

        // ------------------------------------------------------------------
        // 1. Store raw webhook payload for audit / replay
        // ------------------------------------------------------------------
        try {
            $db->table('courier_webhooks')->insert([
                'provider'       => 'steadfast',
                'consignment_id' => $data['consignment_id'] ?? null,
                'order_id'       => $data['invoice']        ?? null,
                'payload'        => json_encode($data),
                'headers'        => json_encode($request->getHeaders()),
            ]);
        } catch (DatabaseException $e) {
            log_message('error', 'Failed to store raw Steadfast webhook: ' . $e->getMessage());
        }

        // ------------------------------------------------------------------
        // 2. Bearer token authentication (Authorization: Bearer <token>)
        // ------------------------------------------------------------------
        $incomingToken = $request->getHeaderLine('Authorization');
        if ($authToken && $incomingToken !== 'Bearer ' . $authToken) {
            return $response->setStatusCode(401)->setBody('Unauthorized');
        }

        // ------------------------------------------------------------------
        // 3. Idempotency – skip duplicates in production
        // ------------------------------------------------------------------
        $hash = md5(json_encode($data));

        if ($env === 'production') {
            $existingEvent = $this->eventModel->where('event_hash', $hash)->first();
            if ($existingEvent) {
                return $response->setStatusCode(200)
                    ->setJSON(['status' => 'success', 'message' => 'Duplicate event ignored']);
            }
        }

        // ------------------------------------------------------------------
        // 4. Resolve normalized status → mapped to existing WC event slugs
        // ------------------------------------------------------------------
        $notificationType = $data['notification_type'] ?? 'tracking_update';
        $normalizedStatus = $this->resolveStatus(
            $notificationType,
            $data['status']           ?? null,
            $data['tracking_message'] ?? null
        );

        // ------------------------------------------------------------------
        // 5. Persist shipment + event inside a transaction
        // ------------------------------------------------------------------
        $db->transStart();

        $shipment = $this->shipmentModel
            ->where('consignment_id', $data['consignment_id'] ?? null)
            ->first();

        if (!$shipment) {
            $shipmentId = $this->shipmentModel->insert([
                'provider'       => 'steadfast',
                'consignment_id' => $data['consignment_id'] ?? null,
                'order_id'       => $data['invoice']        ?? null,
                'cod_amount'     => $data['cod_amount']     ?? null,
                'delivery_fee'   => $data['delivery_charge'] ?? null,
                'current_status' => $normalizedStatus,
                'created_at'     => $data['updated_at'] ?? date('Y-m-d H:i:s'),
                'updated_at'     => $data['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
        } else {
            $shipmentId = $shipment['id'];
            $this->shipmentModel->update($shipmentId, [
                'current_status' => $normalizedStatus,
                'cod_amount'     => $data['cod_amount'] ?? null,
                'updated_at'     => $data['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
        }

        $this->eventModel->insert([
            'shipment_id'       => $shipmentId,
            'provider_event'    => $notificationType,
            'normalized_status' => $normalizedStatus,
            'message'           => $data['tracking_message'] ?? null,
            'event_time'        => $data['updated_at'] ?? date('Y-m-d H:i:s'),
            'event_hash'        => $hash,
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', 'Steadfast webhook transaction failed for consignment_id: ' . ($data['consignment_id'] ?? 'unknown'));
            return $response->setStatusCode(500)->setBody('Internal Server Error');
        }

        // ------------------------------------------------------------------
        // 6. Forward normalized payload to external endpoint (Pathao style)
        //
        // Steadfast → Pathao field mapping:
        //   invoice          → merchant_order_id
        //   consignment_id   → consignment_id       (same)
        //   updated_at       → updated_at + timestamp (ISO 8601)
        //   notification_type + status/message → event (e.g. "order.delivery_success")
        //   delivery_charge  → delivery_fee
        // ------------------------------------------------------------------
        $updatedAt = $data['updated_at'] ?? date('Y-m-d H:i:s');

        $forwardPayload = [
            'consignment_id'    => $data['consignment_id'] ?? null,
            'merchant_order_id' => $data['invoice']        ?? null,
            'updated_at'        => $updatedAt,
            'timestamp'         => (new \DateTime($updatedAt))->format(\DateTime::ATOM),
            'event'             => 'order.' . $normalizedStatus,
        ];

        // Only include delivery_fee when present (mirrors Pathao — not all events carry it)
        if (!empty($data['delivery_charge'])) {
            $forwardPayload['delivery_fee'] = $data['delivery_charge'];
        }

        $webhook = new ExternalWebhook();
        $webhook->send($forwardPayload);

        // ------------------------------------------------------------------
        // 7. Respond 200 OK (Steadfast expects HTTP 200 on success)
        // ------------------------------------------------------------------
        return $response->setStatusCode(200)
            ->setJSON(['status' => 'success', 'message' => 'Webhook received successfully.']);
    }
}