<?php

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
    $routes->post('api/webhook/courier/(:segment)', 'CourierWebhook::receive/$1');
});
