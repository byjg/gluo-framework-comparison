<?php

use CodeIgniter\Router\RouteCollection;

/**
 * An API only, for Gluo's SPA: no pages, so neither the welcome page nor Shield's login screens
 *
 * @var RouteCollection $routes
 */
$routes->group('', ['filter' => 'cors'], static function (RouteCollection $routes): void {
    $routes->post('login', 'AuthController::login');

    $routes->group('', ['filter' => 'jwt'], static function (RouteCollection $routes): void {
        $routes->post('refreshtoken', 'AuthController::refresh');
        $routes->resource('product', ['controller' => 'ProductController', 'except' => 'new,edit']);
    });

    // The browser's preflight: the cors filter answers it
    $routes->options('(:any)', static function (): void {});
});
