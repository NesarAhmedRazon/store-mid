<?php

namespace App\Controllers\Product;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CategoryModel;

class AllCats extends ResourceController
{
    protected $format = 'json';

    public function send(): \CodeIgniter\HTTP\ResponseInterface
    {
        $model = new CategoryModel();  // Load manually
        
        $categories = $model->getTree();
        $counts     = $model->getProductCountsAll();

        foreach ($categories as $category) {
            $category->product_count = $counts[$category->id] ?? 0;
        }
 
        return $this->respond([
            'status'  => 'ok',
            'categories' => $categories,
        ]);
    }
}