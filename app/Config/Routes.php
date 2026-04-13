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

    // Endpoints to handle WP post types. Product.
    $routes->group('posts', function ($routes) {
        $routes->post('product', 'Product::receive');
    });

    
    
});

$routes->group('api/get', function ($routes) {
    $routes->get('products/(:segment)', 'EndpointProduct::send/$1');
    $routes->get('products', 'EndpointProduct::send');

    $routes->get('categories', 'EndpointCategory::send');
    
});

$routes->get('/', 'Auth::login');
$routes->post('/login', 'Auth::attempt');
$routes->get('/logout', 'Auth::logout');

$routes->group('', ['filter' => 'auth'], function($routes) {

    // All authenticated users
    $routes->get('/dashboard', 'Dashboard::index');

    // Products
    $routes->group('products', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'AdminProducts::index');
        $routes->get('preview', 'AdminProducts::preview');
                
    
        // Product Categories
        $routes->group('categories', ['filter' => 'auth'], function($routes) {
                $routes->get('/',              'AdminCategories::index');
                $routes->get('(:num)',         'AdminCategories::preview/$1');
                $routes->get('create',         'AdminCategories::create');
                $routes->post('store',         'AdminCategories::store');
                $routes->get('(:num)/edit',    'AdminCategories::edit/$1');
                $routes->post('(:num)/update', 'AdminCategories::update/$1');
                $routes->get('(:num)/delete',  'AdminCategories::delete/$1');
            });
    });
    
    
    
    // Admin + Staff
    $routes->group('', ['filter' => 'role:admin,staff'], function($routes) {
        $routes->get('/reports', 'Reports::index');
    });

    // Admin only
    $routes->group('', ['filter' => 'role:admin'], function($routes) {
        $routes->get('/users', 'Admin\Users::index');
        $routes->post('/users/create', 'Admin\Users::create');
        $routes->post('/users/(:num)/role', 'Admin\Users::updateRole/$1');
    });

    // Product
    // $routes->group('/product',function($routes){

    // });

});