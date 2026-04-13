<?php

/*
* directory: app/Controllers/CourierWebhook.php
* description: Handles incoming webhook notifications from courier providers (Pathao and Steadfast)
*/

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Couriers\PathaoCourier;
use App\Couriers\SteadfastCourier;

class CourierWebhook extends ResourceController
{
    /**
     * Entry point for webhooks
     * $provider = 'pathao' or 'steadfast'
     */



    public function receive($provider)
    {
        $data = $this->request->getJSON(true);
        if (!$data) return $this->fail('Invalid JSON', 400);

        $providerRow = db_connect()->table('courier_providers')->where('name', $provider)->get()->getRow();

        switch ($provider) {
            case 'pathao':
                $courier = new PathaoCourier();
                return $courier->handle(
                    $data,
                    $providerRow->auth_token,
                    $providerRow->webhook_secret,
                    $this->request,   // <--- Pass CI request
                    $this->response   // <--- Pass CI response
                );
            case 'steadfast':
                return (new SteadfastCourier())->handle(
                    $data,
                    $providerRow->auth_token,
                    $this->request,
                    $this->response
                );
            default:
                return $this->fail('Unknown provider', 400);
        }
    }
    
}