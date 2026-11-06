---
sidebar_position: 110
title: "API: Laravel"
---

# API: Laravel

Laravel 13 as a JSON API only, for Gluo's React SPA, unchanged: Eloquent, Passport (the
first-party OAuth2 server) for the token, MySQL 8.0 in Docker, PHP on the host.

Requirements: PHP >= 8.3, Composer, Docker.

## Why Passport

The SPA needs a JWT: it reads `exp` from the token to know whether the session is still
valid, to send it, and to refresh it in its last 5 minutes. Laravel's two first-party
options differ there:

- **Sanctum** issues opaque tokens (`12|Xk9f…`), a reference to a database row with nothing
  inside: the SPA cannot read an expiry from it.
- **Passport** issues its access tokens as JWTs (RS256) with `exp`. Like Sanctum, it still
  checks the database on every request, to see whether the token was revoked.

So this build uses Passport: first-party, and the SPA works without a change.

## 1. Create the project and its database

```bash
cd frameworks/api
composer create-project laravel/laravel laravel
cd laravel
```

`frameworks/api/laravel/docker-compose.yml`, MySQL on 3312 so it runs next to the other builds. The
`name` matters: without it Compose names the project after the folder, `laravel`, the same
as the `laravel/` build, and replaces that build's database container.

```yaml
name: api-laravel

services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: mysqlp455w0rd
      MYSQL_DATABASE: laravel
      TZ: UTC
    ports:
      - "3312:3306"
    volumes:
      - mysql-data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-uroot", "-pmysqlp455w0rd"]
      interval: 2s
      retries: 30

volumes:
  mysql-data:
```

In `.env` (and `.env.example`, which a clone copies), replace the SQLite default:

```ini
APP_URL=http://localhost:9081
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3312
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=mysqlp455w0rd
```

```bash
docker compose up -d --wait
rm database/database.sqlite
```

## 2. Install the API with Passport

```bash
php artisan install:api --passport --without-migration-prompt
```

It installs `laravel/passport`, writes `routes/api.php`, publishes the OAuth tables and
generates the signing keys (`storage/oauth-*.key`, not committed: run
`php artisan passport:keys` after a clone).

## 3. Generate the skeleton

```bash
php artisan make:migration add_role_to_users_table --table=users
php artisan make:model Product --migration --seed
php artisan make:controller ProductController --api --model=Product --requests
php artisan make:resource ProductResource
php artisan make:controller AuthController
php artisan config:publish cors
```

## 4. Fill in the generated files

**`config/auth.php`** — the `api` guard Passport authenticates with:

```php
'api' => [
    'driver' => 'passport',
    'provider' => 'users',
],
```

**`app/Models/User.php`** — implement `Laravel\Passport\Contracts\OAuthenticatable`, use
`Laravel\Passport\HasApiTokens`, and add `role` to `#[Fillable]`.

**`database/migrations/*_add_role_to_users_table.php`** — `$table->string('role', 20)->default('user')->after('password');` (and `dropColumn('role')` in `down`).

**`database/migrations/*_create_products_table.php`** — the same table as the other builds:

```php
$table->id();
$table->string('name', 100);
$table->decimal('price', 10, 2);
$table->timestamps();
$table->softDeletes();
```

**`app/Models/Product.php`** — `#[Fillable(['name', 'price'])]`, `use SoftDeletes;` and
`'price' => 'float'` in `casts()`, so the JSON has a number.

**`app/Http/Resources/ProductResource.php`** — the JSON the SPA reads:

```php
return [
    'id' => $this->id,
    'name' => $this->name,
    'price' => $this->price,
    'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
    'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
    'deletedAt' => $this->deleted_at?->format('Y-m-d H:i:s'),
];
```

**`app/Providers/AppServiceProvider.php`**, in `boot()`:

```php
// The SPA reads the list as a plain array, not under "data"
JsonResource::withoutWrapping();

// The SPA refreshes the token in its last 5 minutes
Passport::personalAccessTokensExpireIn(now()->addHour());

Gate::define('admin', fn (User $user) => $user->role === 'admin');
```

**`app/Http/Requests/StoreProductRequest.php`** and **`UpdateProductRequest.php`** —
`authorize()` returns `true` (the route checks the role), and:

```php
'name' => ['required', 'string', 'max:100'],
'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
```

**`app/Http/Controllers/ProductController.php`** — the five generated actions:

- `index`: `?page=` (from 0), `?size=` and `?orderBy=name desc,id`, the fields checked
  against a list and converted with `Str::snake()`; `ProductResource::collection(...)`
- `store`: `Product::create($request->validated())`
- `show`: the bound `Product` (route model binding answers 404, also for a deleted one)
- `update`: `$product->update($request->validated())`
- `destroy`: `$product->delete()` (soft delete), `response()->noContent()`

**`app/Http/Controllers/AuthController.php`**:

- `login`: validates `username` and `password`, finds the user by email, checks the
  password with `Hash::check()` (401 otherwise), and answers
  `{token, data: {userid, name, role}}` with `$user->createToken('spa')->accessToken`
- `refresh`: revokes the current token (`$request->user()->token()->revoke()`) and answers
  a new one the same way

**`routes/api.php`**:

```php
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/refreshtoken', [AuthController::class, 'refresh']);

    Route::apiResource('product', ProductController::class)->only(['index', 'show']);
    Route::apiResource('product', ProductController::class)->only(['store', 'update', 'destroy'])
        ->middleware('can:admin');
});
```

**`bootstrap/app.php`** — the SPA calls `/login` and `/product`, without the `/api`
prefix, and sends no `Accept: application/json`:

```php
->withRouting(
    // ...
    api: __DIR__.'/../routes/api.php',
    apiPrefix: '',
)
->withMiddleware(function (Middleware $middleware): void {
    // An API has no login page to send a guest to: auth:api answers 401
    $middleware->redirectGuestsTo(fn (Request $request) => null);
})
->withExceptions(function (Exceptions $exceptions): void {
    // An API only: every error is JSON
    $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);
})
```

**`config/cors.php`** — `'paths' => ['*']` (no `/api` prefix) and
`'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:7081')]`, the SPA.

**`database/seeders/DatabaseSeeder.php`** — the users of the other builds, the personal
access client `createToken()` needs, and the products:

```php
User::create(['name' => 'Administrator', 'email' => 'admin@example.com', 'password' => '!P4ssw0rdstr!', 'role' => 'admin']);
User::create(['name' => 'Regular User', 'email' => 'user@example.com', 'password' => '!P4ssw0rdstr!', 'role' => 'user']);

app(ClientRepository::class)->createPersonalAccessGrantClient('spa', 'users');

$this->call(ProductSeeder::class);
```

**`database/seeders/ProductSeeder.php`** — Keyboard 49.90, Mouse 19.90, Monitor 899.00,
Headset 129.50, Webcam 75.00.

## 5. Run it

```bash
php artisan migrate:fresh --seed
php artisan serve --port=9081
```

The SPA, Gluo's frontend unchanged, from `frameworks/api/frontend`:

```bash
cd ../frontend
docker compose up -d --build laravel
```

Open http://localhost:7081 and log in with `admin@example.com` / `!P4ssw0rdstr!`
(`user@example.com` can read, not write).

Check the API against the contract the SPA relies on, from the repository root:

```bash
php frameworks/api/contract-test.php http://localhost:9081 http://localhost:7081
```

## Endpoints

| Method | Path | Auth | Result |
|---|---|---|---|
| POST | `/login` | — | `{"token": "<JWT>", "data": {...}}`, 401 on a wrong password |
| POST | `/refreshtoken` | token | a new token; the old one is revoked |
| GET | `/product` | token | list; `?page=`, `?size=`, `?orderBy=name desc,id` |
| GET | `/product/{id}` | token | product, 404 if missing or deleted |
| POST | `/product` | token, admin | created product, 422 on validation, 403 for a user |
| PUT | `/product/{id}` | token, admin | updated product |
| DELETE | `/product/{id}` | token, admin | 204, soft delete |

## What was generated vs written

| | |
|---|---|
| Generated by commands | project, API routes file, Passport (tables, keys), migrations, model, controllers, form requests, resource, seeder, CORS config |
| Written by hand | `docker-compose.yml`, `.env`, the `api` guard, the role column, the product table, the resource fields, the 5 controller actions, login and refresh, the admin gate, the routes, 3 lines in `bootstrap/app.php`, CORS paths and origin, the seeders |
