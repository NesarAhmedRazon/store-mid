<?php

/*
* directory: app/Models/ShipmentEventModel.php
* description: Model for shipment timeline events stored in the shipment_events table.
* Used by webhook processors and shipment API endpoints.
*/

namespace App\Models;

use CodeIgniter\Model;

class ShipmentEventModel extends Model
{
    protected $table = 'shipment_events';

    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = '';

    protected $allowedFields = [
        'shipment_id',
        'provider_event',
        'normalized_status',
        'message',
        'event_time',
        'event_hash'
    ];

    protected $validationRules = [
        'shipment_id' => 'required|integer',
        'provider_event' => 'required|string',
        'normalized_status' => 'required|string',
    ];

    /**
     * Get timeline events for a shipment
     */
    public function getTimeline(int $shipmentId)
    {
        return $this->where('shipment_id', $shipmentId)
            ->orderBy('event_time', 'ASC')
            ->findAll();
    }

    /**
     * Check if event already exists using hash (idempotency)
     */
    public function eventExists(string $hash): bool
    {
        return $this->where('event_hash', $hash)->countAllResults() > 0;
    }

    /**
     * Insert event safely (skip duplicates)
     */
    public function insertEvent(array $data)
    {
        if ($this->eventExists($data['event_hash'])) {
            return false;
        }

        return $this->insert($data);
    }
}