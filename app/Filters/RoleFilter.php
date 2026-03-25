<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Enums\UserRole;

/**
 * RoleFilter
 *
 * Restricts access to routes based on the authenticated user's role.
 *
 * Usage in app/Config/Filters.php aliases:
 *
 *   'role' => \App\Filters\RoleFilter::class,
 *
 * Usage in app/Config/Routes.php:
 *
 *   $routes->group('admin', ['filter' => 'role:admin'], function ($routes) {
 *       $routes->get('/', 'Admin\Dashboard::index');
 *   });
 *
 *   $routes->get('reports', 'Reports::index', ['filter' => 'role:admin,staff']);
 *
 * The filter expects AuthFilter to run first (i.e. the user is already
 * confirmed to be logged in before this filter checks the role).
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Ensure the user is logged in before checking role.
        // This is a safety net; AuthFilter should normally run first.
        if (!session()->get('logged_in')) {
            return redirect()->to('/');
        }

        // No role restrictions specified — allow all authenticated users.
        if (empty($arguments)) {
            return;
        }

        $userRole = session()->get('role');

        // Validate that the session role is a legitimate enum value.
        $validRole = UserRole::tryFrom((string) $userRole);

        if ($validRole === null || !in_array($validRole->value, $arguments, true)) {
            // Authenticated but not authorised.
            return redirect()
                ->to('/dashboard')
                ->with('error', 'You do not have permission to access that page.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed after the response.
    }
}
