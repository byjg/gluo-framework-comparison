---
sidebar_position: 20
title: Building it with Gluo
---

# Gluo

byjg/gluo 7.0 + MicroOrm + JWT API, with the bundled React frontend, on Docker with
MySQL 8.0.

Requirements: PHP >= 8.3, Composer, Docker.

Sources: the [Gluo documentation](https://opensource.byjg.com/docs/php/gluo/), its
[unattended setup guide](https://opensource.byjg.com/docs/php/gluo/getting-started/unattended-setup) and the
[`gluo-developer` skill](https://opensource.byjg.com/docs/ai/agent-skills/gluo-developer), which the created
project declares in `.claude/settings.json`.

## 1. Create the project

The installer asks a few questions. To answer them up front, put a `setup.json` next to
where the project will be created (this repository keeps it at the root):

```json
{
  "namespace": "App",
  "composer_name": "byjg/gluo-product-demo",
  "install_frontend": true,
  "install_examples": false
}
```

```bash
composer create-project byjg/gluo gluo ^7.0 --stability=dev
cd gluo
```

> `--stability=dev` is needed until 7.0 is tagged: Packagist only has the `7.0.x-dev`
> branch (the newest stable is 6.1), and without the flag the command fails with
> `Could not find package byjg/gluo with version ^7.0`.

`namespace` is the PHP namespace of the application code; `App` is the same one Laravel
and Symfony use, so the three projects read alike (`App\Model\Product`,
`App\Models\Product`, `App\Entity\Product`).

`install_frontend: true` keeps the React SPA (login, password reset, dashboard,
profile) already wired to the API over JWT. `install_examples: false` removes the
Project/Task/Note demo, leaving auth + profile only. LOGIN is done: `POST /login` on the
API and the login screen on the frontend.

## 2. Start it

```bash
docker compose up -d
php gluo --env=dev migrate:reset
```

`php gluo` is the project's command line: `php gluo list` shows every command, and
`php gluo --env=dev list --check` verifies that each one can run (environment,
configuration, database connection). The older `composer migrate`, `composer codegen` and
`composer openapi` scripts still work as aliases.

API on http://localhost:8080, frontend on http://localhost:7080, MySQL on 3306.

MySQL is the `gluo-database` service of `docker-compose.database.yml`. The commands run on
the host (`php gluo`, `php gluo test`) reach it on the published port: `config/dev/credentials.env`
has `127.0.0.1`. In the API container `127.0.0.1` is the container itself, so `docker-compose.yml`
sets `DBDRIVER_CONNECTION` to the service name, `gluo-database`, which wins over the file.

The frontend also answers over HTTPS on https://localhost:7443. Its web server,
`byjg/static-httpserver`, generates a self-signed certificate when it starts, so there
is nothing to configure; the browser shows its usual warning for an unknown certificate.
The API itself stays on plain HTTP.
`reset` creates the schema from `db/base.sql`, including the seed user
`admin@example.com` / `!P4ssw0rdstr!`.

## 3. Write the migration

Gluo is database-first: migrations are plain SQL, and the code is generated from the
table.

`db/migrations/up/00001-create-table-product.sql`

```sql
create table product
(
    id int auto_increment not null primary key,
    name varchar(100) not null,
    price decimal(10,2) not null,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

insert into product (name, price) values
    ('Keyboard', 49.90),
    ('Mouse', 19.90),
    ('Monitor', 899.00),
    ('Headset', 129.50),
    ('Webcam', 75.00);
```

`db/migrations/down/00000-rollback-table-product.sql`

```sql
drop table product;
```

```bash
php gluo --env=dev migrate:update
```

## 4. Generate the CRUD from the table

```bash
php gluo --env=dev codegen:all --table=product --activerecord --save
php gluo openapi
```

`codegen` reads the `product` table and writes, complete and working:

- `src/Model/Product.php` — fields, types, OpenAPI schema, with `maxLength: 100` and
  `minLength: 1` taken from the `varchar(100) not null` column (no default, so an empty
  name is refused like a missing one); the key marked `readOnly`, since the database
  generates it; `validator: SchemaValidator::class`, so every save is checked against that
  schema; and, from the three date columns, the `OaCreatedAt` / `OaUpdatedAt` / `OaDeletedAt`
  traits
- `src/Controller/ProductController.php` — `GET /product/{id}`, `GET /product` (paging
  and `?orderBy=`), `POST /product`, `PUT /product/{id}` (the same body as the `POST`), all JWT-protected and validated
  against the OpenAPI schema; writes require the `admin` role
- `tests/Controller/ProductTest.php` — 10 tests that pass as generated, including one that
  sends invalid payloads to the server

`--activerecord` is the two-file pattern (Model + Controller). Without it, codegen
writes the Repository pattern instead (Model + Repository + Service + Controller and
their DI bindings). A single component is `codegen:model`, `codegen:controller`,
`codegen:test`, and so on.

`php gluo openapi` rebuilds `public/docs/openapi.json`, which is the routing table —
without it the new endpoints answer 404.

## 5. Hand edits

**Delete endpoint** — codegen does not write one. Add `deleteProduct` to
`src/Controller/ProductController.php`; the body is one line, the rest is OpenAPI
attributes:

```php
#[OA\Delete(path: "/product/{id}", security: [["jwt-token" => []]], tags: ["Product"])]
#[OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", format: "int32"))]
#[OA\Response(response: 200, description: "Nothing to return")]
#[OA\Response(response: 401, description: "Not Authorized", content: new OA\JsonContent(ref: "#/components/schemas/error"))]
#[RequireRole(User::ROLE_ADMIN)]
public function deleteProduct(HttpResponse $response, HttpRequest $request): void
{
    Product::getOrFail($request->attribute('id'))->delete();
}
```

`getOrFail()` comes from Gluo's `ActiveRecord` trait, which codegen put on the model: it returns
the product or answers 404 `"Product not found"`, like Laravel's `findOrFail()`. The generated
`GET` and `PUT` use it the same way.

Because the model has the `OaDeletedAt` trait, `delete()` sets `deleted_at` and the row
disappears from `get()` / `all()`.

**Price range** — codegen takes the length of `name` from the column, but a column type
does not say which values a price may take. Add `maximum: 99999999.99, minimum: 0` to
the `price` property in two places: the request body of `postProduct` in the controller
and the property in `src/Model/Product.php` (the schema `putProduct` validates against).

```php
new OA\Property(property: "price", type: "number", format: "double", maximum: 99999999.99, minimum: 0),
```

Run `php gluo openapi` again. The API now answers 400 to an empty name, a name over 100
characters or a price outside the range, on create and on update, before anything reaches
the database.

**Tests** — add `testSoftDelete`, `testInvalidProductIsRejectedByTheServer` and
`testAnInvalidProductIsRejectedWhenSavedOutsideTheApi` to `tests/Controller/ProductTest.php`.
The second extends the generated invalid-payload test to every limit: it sends five invalid
payloads, on `POST` and `PUT`, to the server, and checks the 400 and that the table did not
change. The third saves an invalid product directly, as a command would, and checks that the
model's own schema refuses it.

**Frontend** — codegen covers the API only; the screen is written by hand:

- `frontend/src/pages/Products.jsx` — table sorted by name, paging, create/edit modal and
  delete, using the bundled `api` client and `ui` components (including `Pagination`)
- `frontend/src/App.jsx` — `<Route path="/products" element={<Products />} />`
- `frontend/src/components/AppNav.jsx` — the `Products` menu entry

## 6. Test and run

```bash
php gluo --env=dev list --check
php gluo test
composer psalm
docker compose up -d --build frontend
```

Open http://localhost:7080, log in with `admin@example.com` / `!P4ssw0rdstr!` and open
**Products**. Swagger UI for the API is at http://localhost:8080/docs.

Stop with `docker compose down` (add `-v` to drop the database volume).

## Endpoints and screens

| Method | Path | Auth | Result |
|---|---|---|---|
| POST | `/login` | — | `{"token": "...", "data": {...}}` |
| GET | `/product` | JWT | list; `?page=`, `?size=`, `?orderBy=price desc,name` |
| GET | `/product/{id}` | JWT | product, 404 if missing or deleted |
| POST | `/product` | JWT, admin | created product, 400 on validation |
| PUT | `/product/{id}` | JWT, admin | updates the product; the body has the same fields as the `POST` |
| DELETE | `/product/{id}` | JWT, admin | soft delete (sets `deleted_at`) |

Frontend: `/login`, `/dashboard`, `/profile`, `/products`.

## What was generated vs written

| | |
|---|---|
| Generated by commands | project, Docker, login API + login/profile screens, model, controller with 4 endpoints, 10 tests, OpenAPI spec and routes |
| Written by hand | the SQL migration (up + down), the delete endpoint, the price range (2 attributes), 3 tests, 1 React page + route + menu entry |
