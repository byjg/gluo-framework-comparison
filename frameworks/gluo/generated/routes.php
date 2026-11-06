<?php

// The compiled routes (ByJG\RestServer\Route\CompiledRouteList::compile()). Do not edit.

return array (
  'routes' => 
  array (
    'POST /login/confirmcode' => 
    array (
      0 => 'POST',
      1 => '/login/confirmcode',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\LoginController',
        1 => 'postConfirmCode',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/login/confirmcode',
      ),
    ),
    'POST /login/resetpassword' => 
    array (
      0 => 'POST',
      1 => '/login/resetpassword',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\LoginController',
        1 => 'postResetPassword',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/login/resetpassword',
      ),
    ),
    'POST /login/resetrequest' => 
    array (
      0 => 'POST',
      1 => '/login/resetrequest',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\LoginController',
        1 => 'postResetRequest',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/login/resetrequest',
      ),
    ),
    'POST /login' => 
    array (
      0 => 'POST',
      1 => '/login',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\LoginController',
        1 => 'post',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/login',
      ),
    ),
    'GET /product' => 
    array (
      0 => 'GET',
      1 => '/product',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\ProductController',
        1 => 'listProduct',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/product',
      ),
    ),
    'POST /product' => 
    array (
      0 => 'POST',
      1 => '/product',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\ProductController',
        1 => 'postProduct',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/product',
      ),
    ),
    'GET /profile' => 
    array (
      0 => 'GET',
      1 => '/profile',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\ProfileController',
        1 => 'getProfile',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/profile',
      ),
    ),
    'PUT /profile' => 
    array (
      0 => 'PUT',
      1 => '/profile',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\ProfileController',
        1 => 'putProfile',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/profile',
      ),
    ),
    'POST /refreshtoken' => 
    array (
      0 => 'POST',
      1 => '/refreshtoken',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\LoginController',
        1 => 'refreshToken',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/refreshtoken',
      ),
    ),
    'GET /product/{id}' => 
    array (
      0 => 'GET',
      1 => '/product/{id}',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\ProductController',
        1 => 'getProduct',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/product/{id}',
      ),
    ),
    'PUT /product/{id}' => 
    array (
      0 => 'PUT',
      1 => '/product/{id}',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonCleanOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\ProductController',
        1 => 'putProduct',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/product/{id}',
      ),
    ),
    'DELETE /product/{id}' => 
    array (
      0 => 'DELETE',
      1 => '/product/{id}',
      2 => 'ByJG\\RestServer\\OutputProcessor\\JsonCleanOutputProcessor',
      3 => false,
      4 => 
      array (
        0 => 'App\\Controller\\ProductController',
        1 => 'deleteProduct',
      ),
      5 => 
      array (
        'openapi_base_path' => '',
        'openapi_path' => '/product/{id}',
      ),
    ),
  ),
  'dispatch' => 
  array (
    0 => 
    array (
      'POST' => 
      array (
        '/login/confirmcode' => 'POST /login/confirmcode',
        '/login/resetpassword' => 'POST /login/resetpassword',
        '/login/resetrequest' => 'POST /login/resetrequest',
        '/login' => 'POST /login',
        '/product' => 'POST /product',
        '/refreshtoken' => 'POST /refreshtoken',
      ),
      'GET' => 
      array (
        '/product' => 'GET /product',
        '/profile' => 'GET /profile',
      ),
      'PUT' => 
      array (
        '/profile' => 'PUT /profile',
      ),
    ),
    1 => 
    array (
      'GET' => 
      array (
        0 => 
        array (
          'regex' => '~^(?|/product/([^/]+))$~',
          'routeMap' => 
          array (
            2 => 
            array (
              0 => 'GET /product/{id}',
              1 => 
              array (
                'id' => 'id',
              ),
            ),
          ),
        ),
      ),
      'PUT' => 
      array (
        0 => 
        array (
          'regex' => '~^(?|/product/([^/]+))$~',
          'routeMap' => 
          array (
            2 => 
            array (
              0 => 'PUT /product/{id}',
              1 => 
              array (
                'id' => 'id',
              ),
            ),
          ),
        ),
      ),
      'DELETE' => 
      array (
        0 => 
        array (
          'regex' => '~^(?|/product/([^/]+))$~',
          'routeMap' => 
          array (
            2 => 
            array (
              0 => 'DELETE /product/{id}',
              1 => 
              array (
                'id' => 'id',
              ),
            ),
          ),
        ),
      ),
    ),
  ),
);
