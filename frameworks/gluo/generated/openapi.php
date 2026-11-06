<?php

// public/docs/openapi.json as a PHP array (php gluo openapi). Do not edit.

return array (
  'openapi' => '3.1.0',
  'info' => 
  array (
    'title' => 'Gluo API',
    'description' => 'REST API built on Gluo',
    'termsOfService' => 'http://localhost:8080/terms/',
    'contact' => 
    array (
      'email' => 'someone@example.com',
    ),
    'license' => 
    array (
      'name' => 'Proprietary',
    ),
    'version' => '1.0.0',
  ),
  'paths' => 
  array (
    '/login' => 
    array (
      'post' => 
      array (
        'tags' => 
        array (
          0 => 'Login',
        ),
        'summary' => 'Do login',
        'operationId' => 'POST::/login::App\\Controller\\LoginController::post',
        'requestBody' => 
        array (
          'description' => 'The Login Data',
          'required' => true,
          'content' => 
          array (
            'application/json' => 
            array (
              'schema' => 
              array (
                'required' => 
                array (
                  0 => 'username',
                  1 => 'password',
                ),
                'properties' => 
                array (
                  'username' => 
                  array (
                    'description' => 'The Username',
                    'type' => 'string',
                    'format' => 'string',
                  ),
                  'password' => 
                  array (
                    'description' => 'The Password',
                    'type' => 'string',
                    'format' => 'string',
                  ),
                ),
                'type' => 'object',
              ),
            ),
          ),
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'The object to be created',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'required' => 
                  array (
                    0 => 'token',
                  ),
                  'properties' => 
                  array (
                    'token' => 
                    array (
                      'type' => 'string',
                    ),
                    'data' => 
                    array (
                      'properties' => 
                      array (
                        'userid' => 
                        array (
                          'type' => 'string',
                        ),
                        'name' => 
                        array (
                          'type' => 'string',
                        ),
                        'role' => 
                        array (
                          'type' => 'string',
                        ),
                      ),
                      'type' => 'object',
                    ),
                  ),
                  'type' => 'object',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    '/refreshtoken' => 
    array (
      'post' => 
      array (
        'tags' => 
        array (
          0 => 'Login',
        ),
        'summary' => 'Refresh Token',
        'operationId' => 'POST::/refreshtoken::App\\Controller\\LoginController::refreshToken',
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'The refreshed token',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'required' => 
                  array (
                    0 => 'token',
                  ),
                  'properties' => 
                  array (
                    'token' => 
                    array (
                      'type' => 'string',
                    ),
                    'data' => 
                    array (
                      'properties' => 
                      array (
                        'role' => 
                        array (
                          'type' => 'string',
                        ),
                        'userid' => 
                        array (
                          'type' => 'string',
                        ),
                        'name' => 
                        array (
                          'type' => 'string',
                        ),
                      ),
                      'type' => 'object',
                    ),
                  ),
                  'type' => 'object',
                ),
              ),
            ),
          ),
          401 => 
          array (
            'description' => 'Unauthorized',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  '$ref' => '#/components/schemas/error',
                ),
              ),
            ),
          ),
        ),
        'security' => 
        array (
          0 => 
          array (
            'jwt-token' => 
            array (
            ),
          ),
        ),
      ),
    ),
    '/login/resetrequest' => 
    array (
      'post' => 
      array (
        'tags' => 
        array (
          0 => 'Login',
        ),
        'summary' => 'Request a password reset code by email',
        'operationId' => 'POST::/login/resetrequest::App\\Controller\\LoginController::postResetRequest',
        'requestBody' => 
        array (
          'description' => 'The email to have the password reset',
          'content' => 
          array (
            'application/json' => 
            array (
              'schema' => 
              array (
                'required' => 
                array (
                  0 => 'email',
                ),
                'properties' => 
                array (
                  'email' => 
                  array (
                    'type' => 'string',
                  ),
                ),
                'type' => 'object',
              ),
            ),
          ),
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'The token for reset',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'required' => 
                  array (
                    0 => 'token',
                  ),
                  'properties' => 
                  array (
                    'token' => 
                    array (
                      'type' => 'string',
                    ),
                  ),
                  'type' => 'object',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    '/login/confirmcode' => 
    array (
      'post' => 
      array (
        'tags' => 
        array (
          0 => 'Login',
        ),
        'summary' => 'Confirm the password reset code',
        'operationId' => 'POST::/login/confirmcode::App\\Controller\\LoginController::postConfirmCode',
        'requestBody' => 
        array (
          'description' => 'The email and code to confirm the password reset',
          'content' => 
          array (
            'application/json' => 
            array (
              'schema' => 
              array (
                'required' => 
                array (
                  0 => 'email',
                  1 => 'token',
                  2 => 'code',
                ),
                'properties' => 
                array (
                  'email' => 
                  array (
                    'type' => 'string',
                  ),
                  'token' => 
                  array (
                    'type' => 'string',
                  ),
                  'code' => 
                  array (
                    'type' => 'string',
                  ),
                ),
                'type' => 'object',
              ),
            ),
          ),
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'The token for reset',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'required' => 
                  array (
                    0 => 'token',
                  ),
                  'properties' => 
                  array (
                    'token' => 
                    array (
                      'type' => 'string',
                    ),
                  ),
                  'type' => 'object',
                ),
              ),
            ),
          ),
          422 => 
          array (
            'description' => 'Invalid data',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  '$ref' => '#/components/schemas/error',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    '/login/resetpassword' => 
    array (
      'post' => 
      array (
        'tags' => 
        array (
          0 => 'Login',
        ),
        'summary' => 'Set a new password with the confirmed reset token',
        'operationId' => 'POST::/login/resetpassword::App\\Controller\\LoginController::postResetPassword',
        'requestBody' => 
        array (
          'description' => 'The email and the new password',
          'content' => 
          array (
            'application/json' => 
            array (
              'schema' => 
              array (
                'required' => 
                array (
                  0 => 'email',
                  1 => 'token',
                  2 => 'password',
                ),
                'properties' => 
                array (
                  'email' => 
                  array (
                    'type' => 'string',
                  ),
                  'token' => 
                  array (
                    'type' => 'string',
                  ),
                  'password' => 
                  array (
                    'type' => 'string',
                  ),
                ),
                'type' => 'object',
              ),
            ),
          ),
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'The token for reset',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'required' => 
                  array (
                    0 => 'token',
                  ),
                  'properties' => 
                  array (
                    'token' => 
                    array (
                      'type' => 'string',
                    ),
                  ),
                  'type' => 'object',
                ),
              ),
            ),
          ),
          422 => 
          array (
            'description' => 'Invalid data',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  '$ref' => '#/components/schemas/error',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    '/product/{id}' => 
    array (
      'get' => 
      array (
        'tags' => 
        array (
          0 => 'Product',
        ),
        'summary' => 'Get the Product by id',
        'operationId' => 'GET::/product/{id}::App\\Controller\\ProductController::getProduct',
        'parameters' => 
        array (
          0 => 
          array (
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => 
            array (
              'type' => 'integer',
              'format' => 'int32',
            ),
          ),
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'The object Product',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  '$ref' => '#/components/schemas/Product',
                ),
              ),
            ),
          ),
        ),
        'security' => 
        array (
          0 => 
          array (
            'jwt-token' => 
            array (
            ),
          ),
        ),
      ),
      'put' => 
      array (
        'tags' => 
        array (
          0 => 'Product',
        ),
        'summary' => 'Update an existing Product',
        'operationId' => 'PUT::/product/{id}::App\\Controller\\ProductController::putProduct',
        'parameters' => 
        array (
          0 => 
          array (
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => 
            array (
              'type' => 'integer',
              'format' => 'int32',
            ),
          ),
        ),
        'requestBody' => 
        array (
          'description' => 'The object Product to be updated',
          'required' => true,
          'content' => 
          array (
            'application/json' => 
            array (
              'schema' => 
              array (
                'required' => 
                array (
                  0 => 'name',
                  1 => 'price',
                ),
                'properties' => 
                array (
                  'name' => 
                  array (
                    'type' => 'string',
                    'format' => 'string',
                    'maxLength' => 100,
                    'minLength' => 1,
                  ),
                  'price' => 
                  array (
                    'type' => 'number',
                    'format' => 'double',
                    'maximum' => 99999999.99,
                    'minimum' => 0,
                  ),
                ),
                'type' => 'object',
              ),
            ),
          ),
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Nothing to return',
          ),
          401 => 
          array (
            'description' => 'Not Authorized',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  '$ref' => '#/components/schemas/error',
                ),
              ),
            ),
          ),
        ),
        'security' => 
        array (
          0 => 
          array (
            'jwt-token' => 
            array (
            ),
          ),
        ),
      ),
      'delete' => 
      array (
        'tags' => 
        array (
          0 => 'Product',
        ),
        'summary' => 'Soft-delete a Product. Because the model uses the OaDeletedAt trait, delete()
sets deleted_at instead of removing the row.',
        'operationId' => 'DELETE::/product/{id}::App\\Controller\\ProductController::deleteProduct',
        'parameters' => 
        array (
          0 => 
          array (
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => 
            array (
              'type' => 'integer',
              'format' => 'int32',
            ),
          ),
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Nothing to return',
          ),
          401 => 
          array (
            'description' => 'Not Authorized',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  '$ref' => '#/components/schemas/error',
                ),
              ),
            ),
          ),
        ),
        'security' => 
        array (
          0 => 
          array (
            'jwt-token' => 
            array (
            ),
          ),
        ),
      ),
    ),
    '/product' => 
    array (
      'get' => 
      array (
        'tags' => 
        array (
          0 => 'Product',
        ),
        'summary' => 'List Product',
        'operationId' => 'GET::/product::App\\Controller\\ProductController::listProduct',
        'parameters' => 
        array (
          0 => 
          array (
            'name' => 'page',
            'in' => 'query',
            'description' => 'Page number',
            'required' => false,
            'schema' => 
            array (
              'type' => 'integer',
            ),
          ),
          1 => 
          array (
            'name' => 'size',
            'in' => 'query',
            'description' => 'Page size',
            'required' => false,
            'schema' => 
            array (
              'type' => 'integer',
            ),
          ),
          2 => 
          array (
            'name' => 'orderBy',
            'in' => 'query',
            'description' => 'Fields to sort by, separated by commas; add \' desc\' after a field for descending order. Example: name desc,id',
            'required' => false,
            'schema' => 
            array (
              'type' => 'string',
            ),
          ),
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'The object Product',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'type' => 'array',
                  'items' => 
                  array (
                    '$ref' => '#/components/schemas/Product',
                  ),
                ),
              ),
            ),
          ),
          401 => 
          array (
            'description' => 'Not Authorized',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  '$ref' => '#/components/schemas/error',
                ),
              ),
            ),
          ),
        ),
        'security' => 
        array (
          0 => 
          array (
            'jwt-token' => 
            array (
            ),
          ),
        ),
      ),
      'post' => 
      array (
        'tags' => 
        array (
          0 => 'Product',
        ),
        'summary' => 'Create a new Product',
        'operationId' => 'POST::/product::App\\Controller\\ProductController::postProduct',
        'requestBody' => 
        array (
          'description' => 'The object Product to be created',
          'required' => true,
          'content' => 
          array (
            'application/json' => 
            array (
              'schema' => 
              array (
                'required' => 
                array (
                  0 => 'name',
                  1 => 'price',
                ),
                'properties' => 
                array (
                  'name' => 
                  array (
                    'type' => 'string',
                    'format' => 'string',
                    'maxLength' => 100,
                    'minLength' => 1,
                  ),
                  'price' => 
                  array (
                    'type' => 'number',
                    'format' => 'double',
                    'maximum' => 99999999.99,
                    'minimum' => 0,
                  ),
                ),
                'type' => 'object',
              ),
            ),
          ),
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'The object to be created',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'required' => 
                  array (
                    0 => 'id',
                  ),
                  'properties' => 
                  array (
                    'id' => 
                    array (
                      'type' => 'integer',
                      'format' => 'int32',
                    ),
                  ),
                  'type' => 'object',
                ),
              ),
            ),
          ),
          401 => 
          array (
            'description' => 'Not Authorized',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  '$ref' => '#/components/schemas/error',
                ),
              ),
            ),
          ),
        ),
        'security' => 
        array (
          0 => 
          array (
            'jwt-token' => 
            array (
            ),
          ),
        ),
      ),
    ),
    '/profile' => 
    array (
      'get' => 
      array (
        'tags' => 
        array (
          0 => 'Profile',
        ),
        'summary' => 'Return the profile of the logged-in user (from the JWT).',
        'operationId' => 'GET::/profile::App\\Controller\\ProfileController::getProfile',
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'The profile of the authenticated user',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'required' => 
                  array (
                    0 => 'userid',
                    1 => 'name',
                    2 => 'email',
                  ),
                  'properties' => 
                  array (
                    'userid' => 
                    array (
                      'type' => 'string',
                    ),
                    'name' => 
                    array (
                      'type' => 'string',
                    ),
                    'email' => 
                    array (
                      'type' => 'string',
                    ),
                    'username' => 
                    array (
                      'type' => 'string',
                    ),
                    'language' => 
                    array (
                      'type' => 
                      array (
                        0 => 'string',
                        1 => 'null',
                      ),
                      'enum' => 
                      array (
                        0 => 'en',
                        1 => 'fr',
                        2 => 'pt',
                      ),
                    ),
                  ),
                  'type' => 'object',
                ),
              ),
            ),
          ),
          401 => 
          array (
            'description' => 'Not Authorized',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  '$ref' => '#/components/schemas/error',
                ),
              ),
            ),
          ),
        ),
        'security' => 
        array (
          0 => 
          array (
            'jwt-token' => 
            array (
            ),
          ),
        ),
      ),
      'put' => 
      array (
        'tags' => 
        array (
          0 => 'Profile',
        ),
        'summary' => 'Update the name/email of the logged-in user.',
        'operationId' => 'PUT::/profile::App\\Controller\\ProfileController::putProfile',
        'requestBody' => 
        array (
          'description' => 'The profile fields to update',
          'required' => true,
          'content' => 
          array (
            'application/json' => 
            array (
              'schema' => 
              array (
                'required' => 
                array (
                  0 => 'name',
                  1 => 'email',
                ),
                'properties' => 
                array (
                  'name' => 
                  array (
                    'type' => 'string',
                  ),
                  'email' => 
                  array (
                    'type' => 'string',
                  ),
                  'language' => 
                  array (
                    'type' => 
                    array (
                      0 => 'string',
                      1 => 'null',
                    ),
                    'enum' => 
                    array (
                      0 => 'en',
                      1 => 'fr',
                      2 => 'pt',
                    ),
                  ),
                ),
                'type' => 'object',
              ),
            ),
          ),
        ),
        'responses' => 
        array (
          200 => 
          array (
            'description' => 'Profile updated',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  'required' => 
                  array (
                    0 => 'userid',
                    1 => 'name',
                    2 => 'email',
                  ),
                  'properties' => 
                  array (
                    'userid' => 
                    array (
                      'type' => 'string',
                    ),
                    'name' => 
                    array (
                      'type' => 'string',
                    ),
                    'email' => 
                    array (
                      'type' => 'string',
                    ),
                    'language' => 
                    array (
                      'type' => 
                      array (
                        0 => 'string',
                        1 => 'null',
                      ),
                      'enum' => 
                      array (
                        0 => 'en',
                        1 => 'fr',
                        2 => 'pt',
                      ),
                    ),
                  ),
                  'type' => 'object',
                ),
              ),
            ),
          ),
          401 => 
          array (
            'description' => 'Not Authorized',
            'content' => 
            array (
              'application/json' => 
              array (
                'schema' => 
                array (
                  '$ref' => '#/components/schemas/error',
                ),
              ),
            ),
          ),
        ),
        'security' => 
        array (
          0 => 
          array (
            'jwt-token' => 
            array (
            ),
          ),
        ),
      ),
    ),
  ),
  'components' => 
  array (
    'schemas' => 
    array (
      'Product' => 
      array (
        'description' => 'Class Product',
        'required' => 
        array (
          0 => 'id',
          1 => 'name',
          2 => 'price',
        ),
        'properties' => 
        array (
          'createdAt' => 
          array (
            'type' => 
            array (
              0 => 'string',
              1 => 'null',
            ),
            'format' => 'date-time',
            'readOnly' => true,
          ),
          'updatedAt' => 
          array (
            'type' => 
            array (
              0 => 'string',
              1 => 'null',
            ),
            'format' => 'date-time',
            'readOnly' => true,
          ),
          'deletedAt' => 
          array (
            'type' => 
            array (
              0 => 'string',
              1 => 'null',
            ),
            'format' => 'date-time',
            'readOnly' => true,
          ),
          'id' => 
          array (
            'type' => 'integer',
            'format' => 'int32',
            'readOnly' => true,
          ),
          'name' => 
          array (
            'type' => 'string',
            'format' => 'string',
            'maxLength' => 100,
            'minLength' => 1,
          ),
          'price' => 
          array (
            'type' => 'number',
            'format' => 'double',
            'maximum' => 99999999.99,
            'minimum' => 0,
          ),
        ),
        'type' => 'object',
        'xml' => 
        array (
          'name' => 'Product',
        ),
      ),
      'User' => 
      array (
        'required' => 
        array (
          0 => 'email',
        ),
        'properties' => 
        array (
          'createdAt' => 
          array (
            'type' => 
            array (
              0 => 'string',
              1 => 'null',
            ),
            'format' => 'date-time',
            'readOnly' => true,
          ),
          'updatedAt' => 
          array (
            'type' => 
            array (
              0 => 'string',
              1 => 'null',
            ),
            'format' => 'date-time',
            'readOnly' => true,
          ),
          'deletedAt' => 
          array (
            'type' => 
            array (
              0 => 'string',
              1 => 'null',
            ),
            'format' => 'date-time',
            'readOnly' => true,
          ),
          'userid' => 
          array (
            'type' => 'string',
            'format' => 'string',
          ),
          'name' => 
          array (
            'type' => 'string',
            'format' => 'string',
          ),
          'email' => 
          array (
            'type' => 'string',
            'format' => 'string',
          ),
          'username' => 
          array (
            'type' => 'string',
            'format' => 'string',
          ),
          'password' => 
          array (
            'type' => 'string',
            'format' => 'string',
          ),
          'role' => 
          array (
            'type' => 'string',
            'format' => 'string',
          ),
        ),
        'type' => 'object',
        'xml' => 
        array (
          'name' => 'User',
        ),
      ),
      'error' => 
      array (
        'properties' => 
        array (
          'error' => 
          array (
            'properties' => 
            array (
              'type' => 
              array (
                'description' => 'The exception class',
                'type' => 'string',
              ),
              'message' => 
              array (
                'description' => 'The error message',
                'type' => 'string',
              ),
              'file' => 
              array (
                'description' => 'The file that raised the error',
                'type' => 'string',
              ),
              'line' => 
              array (
                'description' => 'The line of the error',
                'type' => 'integer',
              ),
            ),
            'type' => 'object',
          ),
        ),
        'type' => 'object',
        'externalDocs' => 
        array (
          'description' => 'API reference',
          'url' => 'http://localhost:8080/docs',
        ),
      ),
    ),
    'securitySchemes' => 
    array (
      'jwt-token' => 
      array (
        'type' => 'http',
        'bearerFormat' => 'JWT',
        'scheme' => 'bearer',
      ),
    ),
  ),
  'tags' => 
  array (
    0 => 
    array (
      'name' => 'Login',
      'description' => 'Login',
    ),
    1 => 
    array (
      'name' => 'Product',
      'description' => 'Product',
    ),
    2 => 
    array (
      'name' => 'Profile',
      'description' => 'Profile',
    ),
  ),
);
