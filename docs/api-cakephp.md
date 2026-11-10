---
sidebar_position: 130
title: "API: CakePHP"
---

# API: CakePHP

CakePHP 5.4 as a JSON API only, for Gluo's React SPA, unchanged: the CakePHP ORM, the
official Authentication plugin with its `Jwt` authenticator, `muffin/trash` for soft delete,
MySQL 8.4 in Docker, PHP on the host.

Requirements: PHP >= 8.2, Composer, Docker.

## Why the Jwt authenticator

The Authentication plugin checks a JWT with its `Jwt` authenticator (on
`firebase/php-jwt`), and its documentation issues the token in the login action with
`JWT::encode()`. The login itself is the plugin's `Form` authenticator, on `POST /login`.
The token is an HS256 JWT signed with the application's salt, with `exp`, which the SPA
reads.

## 1. Create the project and add the plugins

```bash
cd frameworks/api
composer create-project cakephp/app cakephp
cd cakephp
composer require cakephp/authentication muffin/trash firebase/php-jwt
bin/cake plugin load Authentication
bin/cake plugin load Muffin/Trash
```

## 2. MySQL in Docker, and the datasource

`compose.yaml`, MySQL on 3314; the `name` keeps it apart from the full-stack CakePHP build's
Compose project:

```yaml
name: api-cakephp

services:
  database:
    image: mysql:8.4
    environment:
      MYSQL_ROOT_PASSWORD: mysqlp455w0rd
      MYSQL_DATABASE: app
      TZ: UTC
    ports:
      - "3314:3306"
    volumes:
      - database_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-uroot", "-pmysqlp455w0rd"]
      interval: 2s
      retries: 30

volumes:
  database_data:
```

`config/app_local.example.php` (and so `app_local.php`), `Datasources.default`:

```php
'host' => '127.0.0.1',
'port' => 3314,
'username' => 'root',
'password' => 'mysqlp455w0rd',
'database' => 'app',
```

```bash
docker compose up -d --wait
```

## 3. Migrations, models and seeds from the command line

```bash
bin/cake bake migration CreateProducts 'name:string[100]' 'price:decimal[10,2]' 'created_at:datetime' 'updated_at:datetime' 'deleted_at:datetime?'
bin/cake bake migration CreateUsers 'email:string[255]:unique' 'password:string[255]' 'name:string[100]' 'role:string[20]' 'created:datetime' 'modified:datetime'
bin/cake migrations migrate
bin/cake bake model Products --no-test --no-fixture
bin/cake bake model Users --no-test --no-fixture
bin/cake bake seed Users
bin/cake bake seed Products
```

The same as the [full-stack CakePHP build](cakephp.md), with `name` and `role` on users,
and only the models: an API has no templates to bake.

## 4. Hand edits

**`src/Model/Table/ProductsTable.php`** — as in the full-stack build: the Timestamp and
Trash behaviors, the three date validators removed, `requirePresence()` without `'create'`,
and the price range:

```php
$this->addBehavior('Timestamp', [
    'events' => ['Model.beforeSave' => ['created_at' => 'new', 'updated_at' => 'always']],
]);
$this->addBehavior('Muffin/Trash.Trash', ['field' => 'deleted_at']);
```

`src/Model/Entity/Product.php` keeps only `name` and `price` in `$_accessible`.

**`src/Model/Entity/User.php`** — `_setPassword()` hashes with the plugin's
`DefaultPasswordHasher` (bcrypt), and `tokenData()` answers `{userid, name, role}`.

**`src/Application.php`**:

- implements `AuthenticationServiceProviderInterface`:

  ```php
  $service = new AuthenticationService();
  $service->loadAuthenticator('Authentication.Form', [
      'loginUrl' => '/login',
      'identifier' => [
          'className' => 'Authentication.Password',
          'fields' => ['username' => 'email', 'password' => 'password'],
      ],
  ]);
  $service->loadAuthenticator('Authentication.Jwt', [
      'secretKey' => Security::getSalt(),
      'returnPayload' => false,
  ]);
  ```

  The SPA posts `username`; the identifier's own `fields` look it up in the `email` column.
  `returnPayload: false` makes the `JwtSubject` identifier load the user from the token's
  `sub`.
- in `middleware()`: `CorsMiddleware` before routing, `AuthenticationMiddleware` after the
  body parser, and **no** `CsrfProtectionMiddleware`: the API is stateless (a JWT, no
  session cookie).

**`src/Middleware/CorsMiddleware.php`** — CakePHP has no CORS middleware, but has a builder
on the response. A preflight is answered before routing and authentication:

```php
$response = $request->getMethod() === 'OPTIONS' ? new Response() : $handler->handle($request);

return $response->cors($request)
    ->allowOrigin(['http://localhost:7083'])
    ->allowMethods(['GET', 'POST', 'PUT', 'DELETE'])
    ->allowHeaders(['Authorization', 'Content-Type'])
    ->maxAge(3600)
    ->build();
```

**`src/Controller/AppController.php`** — loads `Authentication.Authentication` (every action
requires a user) instead of `Flash`, and in `beforeRender()` sets the view class to
`JsonView`. `ErrorController` calls it too, so the errors are JSON (`{message, url, code}`)
also for a request without `Accept: application/json`, which the SPA does not send.

**`config/routes.php`** — an API only, so no pages and no fallback routes:

```php
$routes->scope('/', function (RouteBuilder $builder): void {
    $builder->post('/login', 'Users::login');
    $builder->post('/refreshtoken', 'Users::refresh');
    $builder->resources('Products', ['path' => 'product']);
});
```

**`src/Controller/UsersController.php`** — `login` allowed for guests: when the `Form`
authenticator's result is valid, it answers `{token, data}`, the token
`JWT::encode(['sub' => $user->id, 'exp' => time() + 3600], Security::getSalt(), 'HS256')`;
otherwise 401. `refresh` does the same for the current identity.

**`src/Controller/ProductsController.php`** — the actions `resources()` maps:

- `index`: `?page=` (from 0), `?size=`, `?orderBy=name desc,id` (the fields checked against a
  list), `find()->orderBy(...)->limit($size)->offset($page * $size)`
- `view`: `get()` (404 when missing; the Trash behavior hides a deleted one)
- `add`, `edit`: `saveOrFail()`; the table's validation errors answer 400 as `{message}`
- `delete`: `deleteOrFail()` (the Trash behavior sets `deleted_at`), 204
- the writes throw `ForbiddenException` unless the identity's `role` is `admin`
- the JSON through `serialize`, `toArray()` giving `id, name, price, createdAt, updatedAt,
  deletedAt`

**Seeds** — `config/Seeds/UsersSeed.php`: `admin@example.com` (`admin`) and
`user@example.com` (`user`), the password hashed in the seed with `DefaultPasswordHasher`
(a seed writes rows directly); `ProductsSeed.php`: the five products.

## 5. Run it

```bash
bin/cake seeds run
bin/cake server -H localhost -p 9083
```

`seeds run` asks for a confirmation; `-q` runs it without asking.

The SPA, from `frameworks/api/frontend`:

```bash
docker compose up -d --build cakephp
```

Open http://localhost:7083 and log in with `admin@example.com` / `!P4ssw0rdstr!`.

Check the API against the contract, from the repository root:

```bash
php frameworks/api/contract-test.php http://localhost:9083 http://localhost:7083
```

## Endpoints

| Method | Path | Auth | Result |
|---|---|---|---|
| POST | `/login` | — | `{"token": "<JWT>", "data": {...}}`, 401 on a wrong password |
| POST | `/refreshtoken` | token | a new token (the old one stays valid until its `exp`) |
| GET | `/product` | token | list; `?page=`, `?size=`, `?orderBy=name desc,id` |
| GET | `/product/{id}` | token | product, 404 if missing or deleted |
| POST | `/product` | token, admin | 201 with the product, 400 on validation, 403 for a user |
| PUT | `/product/{id}` | token, admin | updated product |
| DELETE | `/product/{id}` | token, admin | 204, soft delete |

## What was generated vs written

| | |
|---|---|
| Generated by commands | project, 2 complete migrations, 2 tables and entities with their validation, 2 empty seeds |
| Written by hand | `compose.yaml`, the datasource, 2 behaviors and the validation changes, the password hasher and `tokenData()`, the authentication service and the middleware queue, the CORS middleware, the JSON view, the routes, login and refresh, the 5 product actions, the seeds |
