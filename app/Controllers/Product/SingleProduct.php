<?php

namespace App\Controllers\Product;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\ProductFetcher;

class SingleProduct extends ResourceController
{
    protected $format = 'json';

    /**
     * GET /api/get/product/(:segment) , by ID or slug
     * 
     * Returns single product with all metadata, images, and related products.
     * The segment is the product permalink (e.g., "sdb628-boost-converter")
     * 
     * @param string|null $permalink Product permalink
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    
}