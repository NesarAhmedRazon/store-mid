<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Libraries\AttributeService;
use App\Models\CategoryModel;

class AdminCategories extends BaseController
{
    protected CategoryModel $model;

    public function __construct()
    {
        $this->model = new CategoryModel();
    }

    /**
     * Category table — /Category
     */
    public function index()
    {
        $categories = $this->model
            ->orderBy('updated_at', 'DESC')
            ->findAll();
        foreach ($categories as $category) {
            $product_ids = $this->model->getProductIds($category->id);
            $category->product_ids = $product_ids;
            $category->product_count = count($product_ids);
        }

        return view('admin/categories/index', [
            'title'    => 'Categories',
            'categories' => $categories,
        ]);
    }

    /**
     * Product preview — /products/preview?id=1
     */
    public function preview($id)
    {

       
        

        if (!$id) {
            return redirect()->to('/categories')->with('error', 'Invalid Category.');
        }


        $cats         = new CategoryModel();
        $productModel = new \App\Models\ProductModel();
        $category     = $cats->find($id);
        $breadcrumb = $cats->getBreadcrumb($id) ?? [];
        $children = $cats->getChildren($id) ?? [];
        $productIds = $cats->getProductIds($id,true) ?? [];
        
        foreach ($productIds as $productId) {
            $product = $productModel->find($productId);
            if ($product) {
                $products[] = $product;
            }
        }
        

        return view('admin/categories/single/preview', [
            'title'   => $category->name,
            'category' => $category,
            'breadcrumb' => $breadcrumb,
            'children' => $children,
            'products' => $products,

        ]);
    }
}