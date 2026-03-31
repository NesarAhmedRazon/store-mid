<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Libraries\AttributeService;

class Products extends BaseController
{
    protected ProductModel $model;

    public function __construct()
    {
        $this->model = new ProductModel();
    }

    /**
     * Products table — /products
     */
    public function index()
    {
        $products = $this->model
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        return view('products/index', [
            'title'    => 'Products',
            'products' => $products,
        ]);
    }

    /**
     * Product preview — /products/preview?id=1
     */
    public function preview()
    {
        $id = (int) $this->request->getGet('id');
 
        if (!$id) {
            return redirect()->to('/products')->with('error', 'Invalid product ID.');
        }
 
        $product = $this->model->find($id);
 
        if (!$product) {
            return redirect()->to('/products')->with('error', 'Product not found.');
        }
 
        $attr = new AttributeService();
        $product->attributes = $attr->getByProductId($product->id);
 
        $mediaModel      = new \App\Models\MediaModel();
        $media           = $mediaModel->getForEntity('product', $product->id);
        $product->thumb  = $media['thumbnail'];
        $product->gallery = $media['gallery'];
 
        return view('products/preview', [
            'title'   => $product->title,
            'product' => $product,
        ]);
    }
}