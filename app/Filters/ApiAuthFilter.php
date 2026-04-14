<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Config\Services;

class ApiAuthFilter implements FilterInterface
{
    /**
     * This runs BEFORE the controller method.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $secret = $request->getHeaderLine('x-front-webhook-secret');
        $checkSecret = env('FRONT_WEBHOOK_SECRET');

        if (empty($secret) || !hash_equals(trim($checkSecret ?? ''), trim($secret))) {
            return Services::response()
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid or missing webhook secret'
                ])
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}