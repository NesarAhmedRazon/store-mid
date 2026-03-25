<?php

namespace App\Controllers;
use App\Models\ProductModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $products = (new ProductModel())->orderBy('updated_at', 'DESC')->findAll(10);
        return view('dashboard/index', ['products' => $products]);
    }
}
