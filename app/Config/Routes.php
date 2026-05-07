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
        $routes->post('customer', 'Customer\CustomerController::receive');
    });

    // ── Customer social auth (public — no token required) ──────────────
    $routes->group('customer/auth', function ($routes) {
        $routes->post('register', 'Api\CustomerAuthController::register'); // email registration
        $routes->post('login',    'Api\CustomerAuthController::login');    // email login
        $routes->post('google',   'Api\CustomerAuthController::google');
        $routes->post('facebook', 'Api\CustomerAuthController::facebook');
        $routes->post('logout',   'Api\CustomerAuthController::logout');
    });
});

$routes->group('api/get', ['filter' => 'apiAuth'], function ($routes) {
    $routes->get('products', 'Product\AllProducts::send');
    $routes->get('product/(:segment)', 'Product\SingleProduct::show/$1');

    $routes->get('categories', 'EndpointCategoryX::send');
    $routes->get('category/(:segment)', 'EndpointCategoryX::categoryBySlug/$1');

    // ── Customer dashboard endpoints (Bearer token required) ───────────
    // FIX 4: customerAuth filter now registered in Filters.php
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
        // Create
        $routes->get('create',   'AdminProducts::create');
        $routes->post('store',   'AdminProducts::store');

        // Edit / Update
        $routes->get('edit',     'AdminProducts::edit');     // ?id=1
        $routes->put('update',  'AdminProducts::update');   // ?id=1


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

    // Customers — FIX 3: was Customer\CustomersController (extra 's'), corrected throughout
    $routes->group('customers', ['filter' => 'auth'], function ($routes) {
        $routes->get('/',              'Customer\CustomerController::index');
        $routes->get('new',            'Customer\CustomerController::new');
        $routes->post('/',             'Customer\CustomerController::create');
        $routes->get('(:num)',         'Customer\CustomerController::show/$1');
        $routes->get('(:num)/edit',    'Customer\CustomerController::edit/$1');
        $routes->post('(:num)',        'Customer\CustomerController::update/$1');
        $routes->get('(:num)/delete',  'Customer\CustomerController::delete/$1');
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