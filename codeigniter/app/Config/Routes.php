<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', static fn () => redirect()->to('/products'));

$routes->presenter('products', ['controller' => 'ProductController', 'except' => 'show,remove', 'filter' => 'session']);

service('auth')->routes($routes);
