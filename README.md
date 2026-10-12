# Gluo vs Laravel vs Symfony

The same small full-stack application built with
[byjg/gluo](https://opensource.byjg.com/docs/php/gluo/), Laravel and Symfony, each one
following what its own framework recommends, to compare how much each framework gives
you and how each feature is actually done.

Symfony is built twice: once with official packages only, and once on the community
Docker template its documentation points to. Gluo and Laravel ship their own Docker
setup and soft delete, so one build of each covers both.

## The application

| | |
|---|---|
| **Login** | a login screen; every product screen requires an authenticated user |
| **Migration** | a `products` table: `id`, `name` varchar(100), `price` decimal(10,2), `created_at`, `updated_at`, `deleted_at` |
| **CRUD** | list, create, edit and soft-delete a product, from the UI |
| **Database** | MySQL 8 in Docker |
| **Seed user** | `admin@example.com` / `!P4ssw0rdstr!` |

## The builds

| Directory                                                                                             | Stack                                                                                                 | Open                                                                       | MySQL port | Step-by-step guide                                   |
|-------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------|------------|------------------------------------------------------|
| [`gluo/`](https://github.com/byjg/gluo-framework-comparison/tree/master/gluo)                         | byjg/gluo 7.0, MicroOrm (ActiveRecord), JWT API + React SPA                                           | http://localhost:7080 or https://localhost:7443 (API and Swagger on :8080) | 3306       | [docs/gluo.md](docs/gluo.md)                         |
| [`laravel/`](https://github.com/byjg/gluo-framework-comparison/tree/master/laravel)                   | Laravel 13, Eloquent, React starter kit (Inertia + Fortify), Sail                                     | http://localhost:8081                                                      | 3307       | [docs/laravel.md](docs/laravel.md)                   |
| [`symfony-official/`](https://github.com/byjg/gluo-framework-comparison/tree/master/symfony-official) | Symfony 8.1, Doctrine ORM, Twig, form login; official packages only, PHP on the host, MySQL in Docker | http://localhost:8083/product                                              | 3309       | [docs/symfony-official.md](docs/symfony-official.md) |
| [`symfony/`](https://github.com/byjg/gluo-framework-comparison/tree/master/symfony)                   | the same, on the Symfony Docker template (FrankenPHP) with the Gedmo soft-delete bundle               | http://localhost:8082/product                                              | 3308       | [docs/symfony.md](docs/symfony.md)                   |

Each guide lists every command and every hand edit, so any of them can be rebuilt
from an empty directory. The ports do not overlap, so they can all run side by side.

## Side by side

How each feature is done, in short. **[docs/comparison.md](docs/comparison.md)** has the
same table expanded, with the actual code from each project. Where the two Symfony
builds differ, the cell shows both: *official* is `symfony-official/`, *template* is
`symfony/`.

| Feature                      | Gluo                                                                                                                                                      | Laravel                                                | Symfony                                                                                                                                                         |
|------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Create the project**       | `composer create-project byjg/gluo`, answers in `setup.json`                                                                                              | `composer create-project laravel/react-starter-kit`    | *official:* `composer create-project symfony/skeleton` + `composer require webapp`. *template:* clone Symfony Docker, then `composer require webapp`            |
| **Architecture**             | JSON API + separate React SPA                                                                                                                             | one app; React pages rendered through Inertia          | one app; server-rendered Twig                                                                                                                                   |
| **Login**                    | shipped: `POST /login` returns a JWT; SPA login screen                                                                                                    | shipped: Fortify session login; React login screen     | generated: `make:user` + `make:security:form-login`                                                                                                             |
| **Protect a screen**         | `#[RequireAuthenticated]` / `#[RequireRole]` on the method                                                                                                | `auth` middleware on the route group                   | `access_control` in `security.yaml`                                                                                                                             |
| **Migration**                | hand-written SQL, up and down                                                                                                                             | PHP schema builder, written by hand                    | SQL generated by diffing the entity                                                                                                                             |
| **Model**                    | generated from the table                                                                                                                                  | written by hand; columns read at runtime               | generated by `make:entity`, with accessors                                                                                                                      |
| **Model pattern**            | ActiveRecord or Repository, chosen per entity; both generated (ActiveRecord used here)                                                                    | ActiveRecord only                                      | Data Mapper + Repository only                                                                                                                                   |
| **Soft delete + timestamps** | traits, added by codegen from the column names                                                                                                            | `SoftDeletes` trait + `softDeletes()` in the migration | *official:* lifecycle callbacks + a hand-written SQL filter. *template:* community bundle (Gedmo) + 2 traits + 2 YAML blocks                                    |
| **CRUD controller**          | generated, working (get, list, create, update)                                                                                                            | generated empty; bodies by hand                        | generated, working (all five actions)                                                                                                                           |
| **Validation**               | OpenAPI attributes on the model and the endpoint, `#[ValidateRequest]`; types, required fields and lengths generated from the table, value ranges by hand | `FormRequest` rules, by hand                           | `#[Assert\...]` on the entity, by hand                                                                                                                          |
| **Routing**                  | `#[OA\Get]` attributes compiled into `openapi.json`                                                                                                       | `routes/web.php`                                       | `#[Route]` attributes                                                                                                                                           |
| **Screens**                  | React page written by hand                                                                                                                                | React pages written by hand                            | Twig pages generated by `make:crud`                                                                                                                             |
| **API documentation**        | OpenAPI + Swagger UI, always in sync with the routes                                                                                                      | none                                                   | none                                                                                                                                                            |
| **Tests**                    | generated, passing; checked against the OpenAPI contract                                                                                                  | written by hand                                        | generated as placeholders; rewritten                                                                                                                            |
| **Command line**             | `./gluo` (migrate, codegen, openapi, your own commands)                                                                                                   | `php artisan`                                          | `bin/console`                                                                                                                                                   |
| **HTTPS in development**     | frontend: yes, self-signed certificate generated at start; API: HTTP                                                                                      | no (Sail serves HTTP)                                  | *official:* no with `php -S`. *template:* yes, Caddy's local certificate authority (turned off in this build)                                                   |
| **Docker**                   | `docker-compose.yml` shipped                                                                                                                              | Sail, one command                                      | *official:* Flex writes the database service only; PHP runs on the host. *template:* full stack in Docker; both default to PostgreSQL, MySQL is a manual switch |

## Where things live

The first-level folders of each project and their equivalents. The full table, with
notes, is in [docs/comparison.md](docs/comparison.md#15-project-layout).

| Purpose          | Gluo                                                   | Laravel                                                       | Symfony                                                       |
|------------------|--------------------------------------------------------|---------------------------------------------------------------|---------------------------------------------------------------|
| Application code | `src/`                                                 | `app/`                                                        | `src/`                                                        |
| Migrations       | `db/` — plain SQL files                                | `database/migrations/` — PHP classes using the schema builder | `migrations/` — PHP classes wrapping generated SQL            |
| Seed data        | `db/base.sql`                                          | `database/seeders/`, `database/factories/`                    | `src/DataFixtures/`                                           |
| Configuration    | `config/<env>/*.php`                                   | `config/*.php`, `.env`                                        | `config/packages/*.yaml`, `.env`                              |
| Routes           | — (attributes, compiled to `public/docs/openapi.json`) | `routes/`                                                     | — (attributes)                                                |
| Screens          | `frontend/` (separate package)                         | `resources/`                                                  | `templates/`, `assets/`                                       |
| Command line     | `gluo`                                                 | `artisan`                                                     | `bin/console`                                                 |
| Cache and logs   | — (system temp)                                        | `storage/`, `bootstrap/cache/`                                | `var/`                                                        |
| Docker           | `docker/`, `docker-compose.yml`                        | `compose.yaml` (images in `vendor/laravel/sail`)              | `compose.yaml`; the template adds `Dockerfile`, `frankenphp/` |
| Tests            | `tests/`                                               | `tests/`                                                      | `tests/`                                                      |

## What was generated vs written by hand

Lines added or changed by hand for the Product feature, excluding tests, seed data and
blank lines. Everything else came from a command.

|                        | Backend                                                        | Screens                            | Config                                                         |
|------------------------|----------------------------------------------------------------|------------------------------------|----------------------------------------------------------------|
| **Gluo**               | SQL migration 10, delete endpoint ~20, price range 2           | 1 React page + route + menu (~135) | none                                                           |
| **Laravel**            | migration columns 3, model 2, rules 2, controller ~30, route 1 | 2 React pages + type + menu (~140) | `.env` ports                                                   |
| **Symfony** (official) | entity ~15, delete action 1, SQL filter 21                     | none (generated)                   | ~7 lines of YAML, the MySQL switch in 3 files                  |
| **Symfony** (template) | entity 7                                                       | none (generated)                   | ~12 lines of YAML, the MySQL switch in 4 files, 1 template fix |

|                        | Composer packages (prod + dev) | Tests in the project |
|------------------------|--------------------------------|----------------------|
| **Gluo**               | 56 + 25                        | 42                   |
| **Laravel**            | 100 + 38                       | 47                   |
| **Symfony** (official) | 96 + 35                        | 8                    |
| **Symfony** (template) | 98 + 35                        | 8                    |

## Can the code be generated from the model or the table?

|                                                            | Direction                   | What the generator writes                                                      | What is left to write                                                  |
|------------------------------------------------------------|-----------------------------|--------------------------------------------------------------------------------|------------------------------------------------------------------------|
| **Gluo** `./gluo codegen:all`                              | table -> code               | working model, controller (get, list, create, update), tests, OpenAPI          | SQL migration, delete endpoint, frontend screen                        |
| **Symfony** `make:entity` + `make:crud` + `make:migration` | entity -> code and SQL      | entity with accessors, working controller, form, 6 Twig screens, migration SQL | validation, timestamps/soft delete wiring, access control, real tests  |
| **Laravel** `make:model -mfcr`                             | model -> migration -> table | empty skeletons (model, migration, factory, controller)                        | columns, rules, controller bodies, React screens, tests                |

Only Gluo starts from an existing table. Symfony starts from the entity class and
derives the SQL. Laravel has no first-party generator that fills in a CRUD.

## Run them

Dependencies are not in the repository, so each project is installed first. From a
fresh clone:

```bash
# Gluo: API on :8080, frontend on :7080 and https://localhost:7443
cd gluo && composer install && docker compose up -d && ./gluo --env=dev migrate:reset

# Laravel: http://localhost:8081
cd laravel && composer install && cp .env.example .env && php artisan key:generate
./vendor/bin/sail up -d
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
./vendor/bin/sail artisan migrate:fresh --seed

# Symfony, official packages only: http://localhost:8083/product
cd symfony-official && composer install && docker compose up -d --wait
php bin/console doctrine:migrations:migrate -n && php bin/console doctrine:fixtures:load -n
php -S 0.0.0.0:8083 -t public

# Symfony, Docker template: http://localhost:8082/product
# (the container installs the dependencies and runs the migrations when it starts)
cd symfony && docker compose up -d --wait
docker compose exec php bin/console doctrine:fixtures:load -n
```

Log in with `admin@example.com` / `!P4ssw0rdstr!`. Each guide has the commands for the
tests and for shutting down.

The environment files in the repository hold generated development secrets and
placeholder credentials only. None of these projects is meant to be deployed as is.
