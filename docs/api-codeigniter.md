---
sidebar_position: 140
title: "API: CodeIgniter"
---

# API: CodeIgniter

CodeIgniter 4.7 as a JSON API only, for Gluo's React SPA, unchanged: its Model and Query
Builder, Shield (the official authentication library) with its JWT authenticator, MySQL 8.0
in Docker, PHP on the host.

Requirements: PHP >= 8.2, Composer, Docker.

## Why Shield's JWT

Shield ships a JWT authenticator next to its session and access-token ones. Its access
tokens are opaque (looked up in the database), which the SPA cannot read an expiry from; the
JWT authenticator issues an HS256 JWT with `exp` and checks it by its signature, then loads
the user.

## 1. Create the project and add Shield

```bash
cd frameworks/api
composer create-project codeigniter4/appstarter codeigniter
cd codeigniter
composer require codeigniter4/shield firebase/php-jwt
php spark shield:setup
```

`shield:setup` writes `app/Config/Auth.php`, `AuthGroups.php` and `AuthToken.php`, adds
Shield's routes and turns on session CSRF. It then asks for the sender e-mail: set it in
`app/Config/Email.php` instead (`$fromEmail = 'noreply@example.com'`,
`$fromName = 'Products'`). `firebase/php-jwt` is the library Shield's JWT authenticator uses.

## 2. MySQL in Docker, and the environment

`compose.yaml`, MySQL on 3315; the `name` keeps it apart from the full-stack CodeIgniter
build's Compose project:

```yaml
name: api-codeigniter

services:
  database:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: mysqlp455w0rd
      MYSQL_DATABASE: app
      TZ: UTC
    ports:
      - "3315:3306"
    volumes:
      - database_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-uroot", "-pmysqlp455w0rd"]
      interval: 2s
      retries: 30

volumes:
  database_data:
```

`.env` (from the starter's `env`; this build keeps its values in `.env.example`):

```dotenv
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:9084/'
app.indexPage = ''
database.default.hostname = 127.0.0.1
database.default.database = app
database.default.username = root
database.default.password = mysqlp455w0rd
database.default.DBDriver = MySQLi
database.default.port = 3315
authjwt.keys.default.0.secret = '<php -r "echo base64_encode(random_bytes(32));">'
```

```bash
docker compose up -d --wait
```

## 3. The JWT authenticator

As Shield's documentation says: `app/Config/AuthJWT.php`, extending Shield's, with the
issuer; the secret comes from `.env` (`authjwt.keys.default.0.secret`):

```php
namespace Config;

use CodeIgniter\Shield\Config\AuthJWT as ShieldAuthJWT;

class AuthJWT extends ShieldAuthJWT
{
    public array $defaultClaims = [
        'iss' => 'http://localhost:9084',
    ];
}
```

and in `app/Config/Auth.php`, the authenticator next to the others:

```php
'jwt'     => JWT::class,
```

Tokens live an hour (`$timeToLive = HOUR`), and the `jwt` filter (registered by Shield)
protects the routes.

## 4. Generate the skeleton

```bash
php spark make:scaffold Product --restful controller --table products --suffix
php spark make:controller AuthController
```

As in the full-stack build, the four scaffolded files are empty: the migration, the model,
the seeder, and a `ResourceController` with one method per action.

## 5. Fill in the files

**Migration** and **model** — the same as the
[full-stack CodeIgniter build](codeigniter.md): the `products` table with Forge, and on
`ProductModel` `$useSoftDeletes`, `$useTimestamps`, `$allowedFields = ['name', 'price']`
and the validation rules. One more line: `$casts = ['id' => 'int', 'price' => 'float']`,
so the JSON has numbers.

**`app/Config/Cors.php`** — the built-in `cors` filter's settings:

```php
'allowedOrigins' => ['http://localhost:7084'],
'allowedHeaders' => ['Authorization', 'Content-Type'],
'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
```

**`app/Config/Routes.php`** — an API only, so neither the welcome page nor Shield's login
screens:

```php
$routes->group('', ['filter' => 'cors'], static function (RouteCollection $routes): void {
    $routes->post('login', 'AuthController::login');

    $routes->group('', ['filter' => 'jwt'], static function (RouteCollection $routes): void {
        $routes->post('refreshtoken', 'AuthController::refresh');
        $routes->resource('product', ['controller' => 'ProductController', 'except' => 'new,edit']);
    });

    // The browser's preflight: the cors filter answers it
    $routes->options('(:any)', static function (): void {});
});
```

**`app/Controllers/AuthController.php`** — `login()` checks the password with
`auth('session')->check(['email' => ..., 'password' => ...])` (401 with the reason
otherwise), and answers `{token, data: {userid, name, role}}`, the token from
`service('jwtmanager')->generateToken($user)`. `refresh()` does the same for
`auth('jwt')->user()`. `name` is Shield's `username`; `role` is `admin` for the `admin`
group.

**`app/Controllers/ProductController.php`** — the generated `ResourceController`, with
`$modelName = ProductModel::class` and `$format = 'json'`:

- `index`: `?page=` (from 0), `?size=`, `?orderBy=name desc,id` (the fields checked against a
  list), `$this->model->orderBy(...)->findAll($size, $page * $size)`
- `show`: `find()`, `failNotFound()` when missing (or deleted)
- `create`, `update`: the model validates on `insert()` / `update()`; a failure answers 400
  with the errors as `{message}`
- `delete`: `$this->model->delete($id)` (soft delete), `respondNoContent()`
- the writes answer `failForbidden()` unless the user is in the `admin` group: Shield's
  `group` filter redirects, which suits pages, not an API
- `toArray()`: `id, name, price, createdAt, updatedAt, deletedAt`

**`app/Database/Seeds/ProductSeeder.php`** — `admin@example.com` (group `admin`) and
`user@example.com` (group `user`) through Shield's user provider, password `!P4ssw0rdstr!`,
and the five products.

## 6. Run it

```bash
php spark migrate --all
php spark db:seed ProductSeeder
php spark serve --host localhost --port 9084
```

The SPA, from `frameworks/api/frontend`:

```bash
docker compose up -d --build codeigniter
```

Open http://localhost:7084 and log in with `admin@example.com` / `!P4ssw0rdstr!`.

Check the API against the contract, from the repository root:

```bash
php frameworks/api/contract-test.php http://localhost:9084 http://localhost:7084
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
| Generated by commands | project, Shield's configuration, the empty skeletons of 4 files, the auth controller |
| Written by hand | `compose.yaml`, `.env`, the sender e-mail, `AuthJWT.php` and 1 line in `Auth.php`, the migration, the model's settings, the CORS settings, the routes, login and refresh, the 5 product actions, the seeder |
