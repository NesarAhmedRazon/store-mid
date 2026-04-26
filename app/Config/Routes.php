<?php
/*
* directory: app/Config/Routes.php
* description: Defines the routing rules for the application, mapping URLs to controller methods.
*/

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// $routes->get('/', 'Home::index');
$routes->group('api', function ($routes) {

    $routes->get('shipments/(:segment)/status', 'Shipments::getStatusByOrder/$1'); // e.g. GET /api/shipments/12345/status to get current status of shipment for order_id 12345
    $routes->get('shipments/(:segment)', 'Shipments::getByOrder/$1');
    $routes->get('shipments/consignment/(:segment)', 'Shipments::getByConsignment/$1');
    $routes->get('shipments', 'Shipments::list');
    $routes->get('shipments/(:segment)/events', 'Shipments::events/$1');

    $routes->post('courier/(:segment)', 'CourierWebhook::receive/$1');

    $routes->group('order', function ($routes) {
        $routes->post('new/(:segment)', 'NewOrder::receive/$1');
    });

    // Endpoints to handle WP post types. Product,customers.
    $routes->group('posts', function ($routes) {
        $routes->post('product', 'Product::receive');
        $routes->post('customers', 'Api\CustomersController::receive');
    });
});

$routes->group('api/get', ['filter' => 'apiAuth'], function ($routes) {
    $routes->get('products', 'Product\AllProducts::send');
    $routes->get('product/(:segment)', 'Product\SingleProduct::show/$1');

    $routes->get('categories', 'EndpointCategoryX::send');
    $routes->get('category/(:segment)', 'EndpointCategoryX::categoryBySlug/$1');

    // Frontend customer dashboard — Bearer token verified by customerAuth.
    $routes->group('customer', ['filter' => 'customerAuth'], function ($routes) {
        $routes->get('me',        'Api\CustomerAuthController::me');
        $routes->put('me',        'Api\CustomerAuthController::updateMe');
        $routes->get('me/orders', 'Api\CustomerAuthController::myOrders');
    });
});

$routes->get('/', 'Auth::login');
$routes->post('/login', 'Auth::attempt');
$routes->get('/logout', 'Auth::logout');

$routes->group('', ['filter' => 'auth'], function ($routes) {

    // All authenticated users
    $routes->get('/dashboard', 'Dashboard::index');

    // Products
    $routes->group('products', ['filter' => 'auth'], function ($routes) {
        $routes->get('/', 'AdminProducts::index');
        $routes->get('preview', 'AdminProducts::preview');


        // Product Categories
        $routes->group('categories', ['filter' => 'auth'], function ($routes) {
            $routes->get('/',              'Product\CategoryController::index');
            $routes->get('ss',              'Product\AllCats::send');
            $routes->get('(:num)',         'Product\CategoryController::preview/$1');
            $routes->get('create',         'Product\CategoryController::create');
            $routes->post('store',         'Product\CategoryController::store');
            $routes->get('(:num)/edit',    'Product\CategoryController::edit/$1');
            $routes->post('(:num)/update', 'Product\CategoryController::update/$1');
            $routes->get('(:num)/delete',  'Product\CategoryController::delete/$1');
            $routes->get('import',         'Product\CategoryController::import');
            $routes->post('import',        'Product\CategoryController::importProcess');
        });
    });

    // ── Admin dashboard CRUD (protect with your existing admin middleware) ─────────
    $routes->group('customers', ['filter' => 'auth'], function ($routes) {
        $routes->get('/',        'Customer\CustomerController::index');
        $routes->get('(:num)',   'Customer\CustomersController::show/$1');
        $routes->post('/',       'Customer\CustomersController::create');
        $routes->put('(:num)',   'Customer\CustomersController::update/$1');
        $routes->delete('(:num)', 'Customer\CustomersController::delete/$1');
    });

    // Admin + Staff
    $routes->group('', ['filter' => 'auth'], function ($routes) {
        $routes->get('/reports', 'Reports::index');
    });

    // Admin only
    $routes->group('', ['filter' => 'auth'], function ($routes) {
        $routes->get('/users', 'Admin\Users::index');
        $routes->post('/users/create', 'Admin\Users::create');
        $routes->post('/users/(:num)/role', 'Admin\Users::updateRole/$1');
    });
});