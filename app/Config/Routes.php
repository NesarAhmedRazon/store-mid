<?php
/*
* directory: app/Config/Routes.php
* description: Defines the routing rules for the application, mapping URLs to controller methods.
*/

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->group('api', function ($routes) {

    $routes->get('shipments/(:segment)', 'Shipments::getByOrder/$1');

    $routes->get('shipments/consignment/(:segment)', 'Shipments::getByConsignment/$1');

    $routes->get('shipments', 'Shipments::list');

    $routes->get('shipments/(:segment)/events', 'Shipments::events/$1');
    $routes->post('courier/(:segment)', 'CourierWebhook::receive/$1');
});
