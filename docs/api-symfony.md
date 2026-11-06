---
sidebar_position: 120
title: "API: Symfony"
---

# API: Symfony

Symfony 8.1 as a JSON API only, for Gluo's React SPA, unchanged: Doctrine ORM,
`lexik/jwt-authentication-bundle` for the token, `nelmio/cors-bundle`, MySQL 8.0 in Docker,
PHP on the host.

Requirements: PHP >= 8.4, Composer, Docker.

## Why lexik

Symfony's security component has no JWT authenticator of its own. The usual choice, in
Symfony's and API Platform's documentation, is LexikJWTAuthenticationBundle: `json_login`
checks the password, and lexik answers with a JWT (RS256, with `exp`), which the SPA reads.
The token is stateless: a request checks its signature and loads the user.

## 1. Create the project and its database

```bash
cd frameworks/api
composer create-project symfony/skeleton:"8.1.*" symfony
cd symfony
composer require orm validator serializer security nelmio/cors-bundle lexik/jwt-authentication-bundle
composer require --dev maker orm-fixtures
```

The Doctrine recipe adds a PostgreSQL `compose.yaml`. Replace it with MySQL on 3313, and
delete `compose.override.yaml`. The `name` keeps the Compose project apart from the
`frameworks/fullstack/symfony` build's.

```yaml
name: api-symfony

services:
  database:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: mysqlp455w0rd
      MYSQL_DATABASE: app
      TZ: UTC
    ports:
      - "3313:3306"
    volumes:
      - database_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-uroot", "-pmysqlp455w0rd"]
      interval: 2s
      retries: 30

volumes:
  database_data:
```

In `.env`:

```ini
DATABASE_URL="mysql://root:mysqlp455w0rd@127.0.0.1:3313/app?serverVersion=8.0.32&charset=utf8mb4"
```

The CORS recipe already allows any `localhost` port (`CORS_ALLOW_ORIGIN`), so the SPA on
7082 needs nothing.

```bash
rm compose.override.yaml
docker compose up -d --wait
php bin/console lexik:jwt:generate-keypair
```

The key pair lands in `config/jwt/` (not committed: run the last command after a clone).

## 2. Generate the skeleton

```bash
php bin/console make:user User --is-entity --identity-property-name=email --with-password
php bin/console make:entity User --field=name:string:100
php bin/console make:entity Product --field=name:string:100 --field=price:decimal:10:2 \
    --field=createdAt:datetime_immutable --field=updatedAt:datetime_immutable --field='deletedAt:datetime_immutable?'
php bin/console make:controller ProductController --no-template
php bin/console make:controller TokenController --no-template
```

The fixtures recipe already created `src/DataFixtures/AppFixtures.php`.

## 3. Hand edits

**Timestamps and soft delete** — the same as the
[full-stack Symfony build](symfony-official.md): `#[ORM\HasLifecycleCallbacks]` with
`onCreate()` / `onUpdate()` on `src/Entity/Product.php`, and the 21-line
`src/Doctrine/SoftDeleteFilter.php` enabled in `config/packages/doctrine.yaml`.

**`config/packages/security.yaml`** — two stateless firewalls: `json_login` on `/login`,
answered by lexik, and `jwt` on everything else.

```yaml
    firewalls:
        login:
            pattern: ^/login$
            stateless: true
            provider: app_user_provider
            json_login:
                check_path: /login
                username_path: username
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
        main:
            stateless: true
            provider: app_user_provider
            jwt: ~

    access_control:
        - { path: ^/login$, roles: PUBLIC_ACCESS }
        - { path: ^/, roles: IS_AUTHENTICATED_FULLY }
```

and the route `json_login` answers, in `config/routes.yaml`:

```yaml
login:
    path: /login
    methods: [POST]
```

**`src/Entity/User.php`** — `tokenData()`: `{userid, name, role}`, with `role` `admin` for
`ROLE_ADMIN`, `user` otherwise. Login and refresh both answer it.

**`src/EventListener/TokenDataListener.php`** — on lexik's
`Events::AUTHENTICATION_SUCCESS`, adds `data` to its `{token}` response.

**`src/Controller/TokenController.php`** — `POST /refreshtoken`:
`$tokens->create($user)` (`JWTTokenManagerInterface`) and the user's `tokenData()`.

**`src/Dto/ProductInput.php`** — the body of `POST` and `PUT`, validated by
`#[MapRequestPayload]`:

```php
#[Assert\NotBlank]
#[Assert\Length(max: 100)]
public string $name = '',

#[Assert\NotNull]
#[Assert\Range(min: 0, max: 99999999.99)]
public ?float $price = null,
```

**`src/Controller/ProductController.php`** — five actions under `#[Route('/product')]`:

- `index`: `?page=` (from 0), `?size=`, `?orderBy=name desc,id` (the fields checked against a
  list), read with the repository's own `findBy([], $order, $size, $page * $size)`
- `show`: the `Product` argument is loaded from `{id}` (404 when missing; the filter hides a
  deleted one)
- `create`, `update`: `#[IsGranted('ROLE_ADMIN')]`, `#[MapRequestPayload] ProductInput`
- `delete`: `#[IsGranted('ROLE_ADMIN')]`, sets `deletedAt`, 204
- `toArray()`: `id, name, price` (as a number), `createdAt, updatedAt, deletedAt`
  (`Y-m-d H:i:s`)

**`src/EventListener/JsonExceptionListener.php`** — Symfony renders an error page in HTML
unless the request asks for JSON, and the SPA sends no `Accept` header. On
`kernel.exception`, an HTTP exception (404, 403, the 422 of `#[MapRequestPayload]`) answers
`{message}` as JSON.

**`src/DataFixtures/AppFixtures.php`** — `admin@example.com` (`ROLE_ADMIN`) and
`user@example.com`, password `!P4ssw0rdstr!` hashed with the configured hasher, and the
five products.

## 4. Run it

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
php -S localhost:9082 -t public
```

The SPA, from `frameworks/api/frontend`:

```bash
docker compose up -d --build symfony
```

Open http://localhost:7082 and log in with `admin@example.com` / `!P4ssw0rdstr!`.

Check the API against the contract, from the repository root:

```bash
php frameworks/api/contract-test.php http://localhost:9082 http://localhost:7082
```

## Endpoints

| Method | Path | Auth | Result |
|---|---|---|---|
| POST | `/login` | — | `{"token": "<JWT>", "data": {...}}`, 401 on a wrong password |
| POST | `/refreshtoken` | token | a new token (the old one stays valid until its `exp`) |
| GET | `/product` | token | list; `?page=`, `?size=`, `?orderBy=name desc,id` |
| GET | `/product/{id}` | token | product, 404 if missing or deleted |
| POST | `/product` | token, admin | 201 with the product, 422 on validation, 403 for a user |
| PUT | `/product/{id}` | token, admin | updated product |
| DELETE | `/product/{id}` | token, admin | 204, soft delete |

## What was generated vs written

| | |
|---|---|
| Generated by commands | project, recipes (Doctrine, security, lexik, CORS, fixtures), JWT key pair, User and Product entities with their repositories, 2 controllers, migration |
| Written by hand | `compose.yaml`, `DATABASE_URL`, 2 lifecycle callbacks, the soft-delete filter, the firewalls and the login route, `tokenData()`, 2 listeners, the refresh action, the input DTO, the 5 product actions, the fixtures |
