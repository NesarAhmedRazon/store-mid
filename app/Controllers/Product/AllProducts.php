<?php

/*
 * directory: app/Controllers/Product/AllProducts.php
 * description: Returns a paginated product listing.
 *
 * Route:  GET /api/get/products
 *
 * Query params:
 *   mode     = minimal | summary | full   (default: full)
 *   page     = integer                    (default: 1)
 *   perPage  = integer | "all"            (default: 20)
 *   category = category slug              (optional — filters by category + descendants)
 *
 * Response:
 *   {
 *     "status":  "ok",
 *     "mode":    "summary",
 *     "page":    1,
 *     "perPage": 20,
 *     "total":   143,
 *     "data":    [ { ...product } ]
 *   }
 */

namespace App\Controllers\Product;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\ProductFetcher;

class AllProducts extends ResourceController
{
    protected $format = 'json';

    public function send(): \CodeIgniter\HTTP\ResponseInterface
    {
        // ------------------------------------------------------------------
        // 1. Inputs
        // ------------------------------------------------------------------
        $modeInput   = $this->request->getGet('mode')     ?? 'full';
        $pageInput   = $this->request->getGet('page')     ?? '1';
        $perPageInput = $this->request->getGet('perPage') ?? '20';
        $category    = $this->request->getGet('category') ?? null;

        $mode = in_array($modeInput, ['minimal', 'summary', 'full']) ? $modeInput : 'full';

        // perPage: accept integer or the string "all"
        $isAll   = ($perPageInput === 'all');
        $perPage = $isAll ? 'all' : max(1, (int) $perPageInput);

        // page: only meaningful when perPage is a number
        $page = $isAll ? 1 : max(1, (int) $pageInput);

        // ------------------------------------------------------------------
        // 2. Fetch via ProductFetcher
        // ------------------------------------------------------------------
        try {
            $fetcher = new ProductFetcher();

            $result = $fetcher->getProducts([
                'mode'         => $mode,
                'perPage'      => $perPage,
                'page'         => $page,
                'categorySlug' => $category,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[AllProducts] ' . $e->getMessage());

            return $this->respond([
                'status'  => 'error',
                'message' => 'An error occurred while fetching products.',
            ], 500);
        }

        // ------------------------------------------------------------------
        // 3. Respond
        // ------------------------------------------------------------------
        return $this->respond([
            'status'  => 'ok',
            'mode'    => $mode,
            'page'    => $isAll ? 'all' : $page,
            'perPage' => $perPage,
            'total'   => $result['total'],
            'data'    => $result['products'],
        ]);
    }
}