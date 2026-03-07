<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AutoMigrate implements FilterInterface
{

    public function before(RequestInterface $request, $arguments = null)
    {
        $migrate = Services::migrations();

        try {
            $migrate->latest();
        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
