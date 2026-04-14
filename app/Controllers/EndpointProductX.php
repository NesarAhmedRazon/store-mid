<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\ProductFetcher;

class EndpointProductX extends ResourceController
{
    protected $format = 'json';

    /**
     * GET /api/get/products
     * GET /api/get/products/{categorySlug}
     *
     * Returns product listings. Authentication is handled globally by ApiAuthFilter.
     */
    public function send(string $categorySlug = null): \CodeIgniter\HTTP\ResponseInterface
    {
        // 1. Inputs gathered from URI or Query Params
        $mode = $this->request->getGet('mode') ?? 'full';
        
        // Ensure mode is valid
        if (!in_array($mode, ['minimal', 'summary', 'full'])) {
            $mode = 'full';
        }

        $perPage = $this->request->getGet('per_page') ?? 10;
        $page    = $this->request->getGet('page') ?? 1;
        
        // Use the URI segment if present, otherwise check the 'category' query param
        $activeCategory = $categorySlug ?? $this->request->getGet('category');

        try {
            // 2. Initialize the Library
            $fetcher = new ProductFetcher();

            // 3. Execute Fetching Logic
            $result = $fetcher->getProducts([
                'mode'         => $mode,
                'perPage'      => $perPage,
                'page'         => (int)$page,
                'categorySlug' => $activeCategory
            ]);

            // 4. Standardized Success Response
            return $this->respond([
                'status'   => 'ok',
                'mode'     => $mode,
                'page'     => (int)$page,
                'perPage'  => $perPage === 'all' ? 'all' : (int)$perPage,
                'total'    => $result['total'],
                'products' => $result['products'],
            ]);

        } catch (\Exception $e) {
            // Log error for internal tracking
            log_message('error', '[EndpointProduct] Exception: ' . $e->getMessage());

            return $this->respond([
                'status'  => 'error',
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }
}