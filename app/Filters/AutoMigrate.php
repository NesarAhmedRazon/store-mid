<?php
/*
* directory: app/Filters/AutoMigrate.php
* description: A filter that automatically runs database migrations before processing any request. This ensures the database schema is always up to date without manual intervention.
*/

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AutoMigrate implements FilterInterface
{
    
public function before(RequestInterface $request, $arguments = null)
{
    if (ENVIRONMENT !== 'development') {
        // Prevent auto-migrations in production
        return;
    }

    $migrate = Services::migrations();

    try {
        $migrate->latest();
    } catch (\Throwable $e) {
        log_message('error', $e->getMessage());
    }
}

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
