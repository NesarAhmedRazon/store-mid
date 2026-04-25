<?php 

// =============================================================================
// File: app/Filters/CustomerAuthFilter.php
// =============================================================================
 
namespace App\Filters;
 
use App\Models\CustomerTokenModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
 
/**
 * CustomerAuthFilter
 *
 * Validates the Bearer token on protected /customer/* routes.
 * Attach via $routes->group(..., ['filter' => 'customerAuth'], ...).
 * Register 'customerAuth' in app/Config/Filters.php aliases.
 */
class CustomerAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');
        preg_match('/^Bearer\s+(\S+)$/i', $header, $m);
        $plain = $m[1] ?? null;
 
        if (!$plain) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'Unauthenticated.']);
        }
 
        $tokens     = new CustomerTokenModel();
        $customerId = $tokens->validate($plain);
 
        if (!$customerId) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'Invalid or expired token.']);
        }
 
        // Stash customer_id on the request so controllers can grab it if needed.
        $request->customerId = $customerId;
    }
 
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed after.
    }
}