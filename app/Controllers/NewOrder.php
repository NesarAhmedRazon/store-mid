<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\OrderModel;

class NewOrder extends ResourceController
{
    public function receive()
    {
        $data = $this->request->getJSON(true);

        if (!$data) {
            return $this->fail('Invalid JSON');
        }

        $model = new OrderModel();

        $insert = [
            'wc_order_id' => $data['wc_order_id'],
            'wc_products' => json_encode($data['wc_products']),
            'wc_total' => $data['wc_total']
        ];

        $model->insert($insert);

        return $this->respond([
            'status' => 'ok',
            'wc_order_id' => $data['wc_order_id']
        ]);
    }
}