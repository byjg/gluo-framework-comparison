---
sidebar_category: gluo
---

# Gluo vs Laravel, Symfony, CakePHP and CodeIgniter

The same small full-stack application built with
[byjg/gluo](https://opensource.byjg.com/docs/php/gluo/), Laravel, Symfony, CakePHP and
CodeIgniter, each one following what its own framework recommends, to compare how much
each framework gives you and how each feature is actually done.

Coming from Drupal, or looking for the command and vocabulary equivalents? See
[Coming from another PHP framework](https://opensource.byjg.com/docs/php/gluo/getting-started/coming-from-laravel-symfony).

Symfony is built twice: once with official packages only, and once on the community
Docker template its documentation points to. Gluo and Laravel ship their own Docker
setup and soft delete, so one build of each covers both. CakePHP and CodeIgniter ship no
Docker setup: both run PHP on the host with MySQL from a hand-written compose file.

## The application

| | |
|---|---|
| **Login** | a login screen; every product screen requires an authenticated user. Who may *write* follows each framework's default and differs: see [Protecting the screens](docs/comparison.md#4-protecting-the-screens) |
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
| [`cakephp/`](https://github.com/byjg/gluo-framework-comparison/tree/master/cakephp)                   | CakePHP 5.4, CakePHP ORM, Authentication plugin, screens baked from the table, Muffin/Trash soft delete; PHP on the host, MySQL in Docker | http://localhost:8084                                              | 3310       | [docs/cakephp.md](docs/cakephp.md)                   |
| [`codeigniter/`](https://github.com/byjg/gluo-framework-comparison/tree/master/codeigniter)           | CodeIgniter 4.7, Model + Query Builder, Shield login, plain PHP views; PHP on the host, MySQL in Docker | http://localhost:8085                                                     | 3311       | [docs/codeigniter.md](docs/codeigniter.md)           |

Each guide lists every command and every hand edit, so any of them can be rebuilt
from an empty directory. The ports do not overlap, so they can all run side by side.

## Side by side

How each feature is done, in short. **[docs/comparison.md](docs/comparison.md)** has the
same table expanded, with the actual code from each project. Where the two Symfony
builds differ, the cell shows both: *official* is `symfony-official/`, *template* is
`symfony/`.

| Feature | Gluo | Laravel | Symfony | CakePHP | CodeIgniter |
|---|---|---|---|---|---|
| **Create the project** | `composer create-project byjg/gluo`, answers in `setup.json` | `composer create-project laravel/react-starter-kit` | *official:* `composer create-project symfony/skeleton` + `composer require webapp`. *template:* clone Symfony Docker, then `composer require webapp` | `composer create-project cakephp/app` | `composer create-project codeigniter4/appstarter` |
| **Architecture** | JSON API + separate React SPA | one app; React pages rendered through Inertia | one app; server-rendered Twig | one app; server-rendered PHP templates | one app; server-rendered PHP views |
| **Login** | shipped: `POST /login` returns a JWT; SPA login screen | shipped: Fortify session login; React login screen | generated: `make:user` + `make:security:form-login` | official Authentication plugin, wired by hand (service, middleware, 2 actions, 1 template) | official Shield package: `composer require` + `shield:setup`; login screens included |
| **Protect a screen** | `#[RequireAuthenticated]` / `#[RequireRole]` on the method | `auth` middleware on the route group | `access_control` in `security.yaml` | the Authentication component on every action; `allowUnauthenticated()` for the exceptions | Shield's `session` filter on the route |
| **Migration** | hand-written SQL, up and down | PHP schema builder, written by hand | SQL generated by diffing the entity | PHP class, generated from the column list on the command line | PHP class using Forge, written by hand |
| **Model** | generated from the table | written by hand; columns read at runtime | generated by `make:entity`, with accessors | baked from the table (Table + Entity) | generated empty; settings filled by hand |
| **Model pattern** | ActiveRecord or Repository, chosen per entity; both generated (ActiveRecord used here) | ActiveRecord only | Data Mapper + Repository only | Table (repository) + Entity | Model (table gateway) returning arrays or entities |
| **Soft delete + timestamps** | traits, added by codegen from the column names | `SoftDeletes` trait + `softDeletes()` in the migration | *official:* lifecycle callbacks + a hand-written SQL filter. *template:* community bundle (Gedmo) + 2 traits + 2 YAML blocks | Timestamp behavior (core) + Trash behavior (community plugin) | `$useTimestamps` / `$useSoftDeletes` on the model (core) |
| **CRUD controller** | generated, working (get, list, create, update) | generated empty; bodies by hand | generated, working (all five actions) | baked, working (all five actions) | generated empty; bodies by hand |
| **Validation** | OpenAPI attributes on the model and the endpoint: `#[ValidateRequest]` on the request, and the model checked again on every `save()`; types, required fields and lengths generated from the table, value ranges by hand | `FormRequest` rules, by hand | `#[Assert\...]` on the entity, by hand | `validationDefault()` baked from the columns; value ranges by hand | `$validationRules` on the model, by hand |
| **Routing** | `#[OA\Get]` attributes compiled into `openapi.json` | `routes/web.php` | `#[Route]` attributes | conventions (`/products/edit/1`) from the fallback routes | `app/Config/Routes.php`, one `presenter()` line |
| **Screens** | React page written by hand | React pages written by hand | Twig pages generated by `make:crud` | 4 templates baked, styled | PHP views written by hand |
| **API documentation** | OpenAPI + Swagger UI, always in sync with the routes | none | none | none | none |
| **Tests** | generated, passing; checked against the OpenAPI contract | written by hand | generated as placeholders; rewritten | baked as placeholders (`markTestIncomplete`); rewritten | written by hand |
| **Command line** | `php gluo` (migrate, codegen, openapi, your own commands) | `php artisan` | `bin/console` | `bin/cake` | `php spark` |
| **HTTPS in development** | frontend: yes, self-signed certificate generated at start; API: HTTP | no (Sail serves HTTP) | *official:* no with `php -S`. *template:* yes, Caddy's local certificate authority (turned off in this build) | no (`bin/cake server`) | no (`php spark serve`) |
| **Docker** | `docker-compose.yml` shipped | Sail, one command | *official:* Flex writes the database service only; PHP runs on the host. *template:* full stack in Docker; both default to PostgreSQL, MySQL is a manual switch | none shipped; MySQL compose written by hand, PHP on the host | none shipped; MySQL compose written by hand, PHP on the host |

## Where things live

The first-level folders of each project and their equivalents. The full table, with
notes, is in [docs/comparison.md](docs/comparison.md#15-project-layout).

| Purpose | Gluo | Laravel | Symfony | CakePHP | CodeIgniter |
|---|---|---|---|---|---|
| Application code | `src/` | `app/` | `src/` | `src/` | `app/` |
| Migrations | `db/` — plain SQL files | `database/migrations/` — PHP classes using the schema builder | `migrations/` — PHP classes wrapping generated SQL | `config/Migrations/` — PHP classes, reversible `change()` | `app/Database/Migrations/` — PHP classes using Forge |
| Seed data | `db/base.sql` | `database/seeders/`, `database/factories/` | `src/DataFixtures/` | `config/Seeds/` | `app/Database/Seeds/` |
| Configuration | `config/<env>/*.php` | `config/*.php`, `.env` | `config/packages/*.yaml`, `.env` | `config/app.php`, `config/app_local.php` | `app/Config/*.php`, `.env` |
| Routes | — (attributes, compiled to `public/docs/openapi.json`) | `routes/` | — (attributes) | `config/routes.php` | `app/Config/Routes.php` |
| Screens | `frontend/` (separate package) | `resources/` | `templates/`, `assets/` | `templates/`, `webroot/` | `app/Views/` |
| Command line | `gluo` | `artisan` | `bin/console` | `bin/cake` | `spark` |
| Cache and logs | — (system temp) | `storage/`, `bootstrap/cache/` | `var/` | `tmp/`, `logs/` | `writable/` |
| Docker | `docker/`, `docker-compose.yml` | `compose.yaml` (images in `vendor/laravel/sail`) | `compose.yaml`; the template adds `Dockerfile`, `frankenphp/` | `compose.yaml`, written by hand | `compose.yaml`, written by hand |
| Tests | `tests/` | `tests/` | `tests/` | `tests/` | `tests/` |

## What was generated vs written by hand

Lines added or changed by hand for the Product feature, excluding tests, seed data and
blank lines. Everything else came from a command.

|                        | Backend                                                        | Screens                            | Config                                                         |
|------------------------|----------------------------------------------------------------|------------------------------------|----------------------------------------------------------------|
| **Gluo**               | SQL migration 10, delete endpoint ~10, price range 2           | 1 React page + route + menu (~160) | none                                                           |
| **Laravel**            | migration columns 3, model 2, rules 2, controller ~30, route 1 | 2 React pages + type + menu (~140) | `.env` ports                                                   |
| **Symfony** (official) | entity ~15, delete action 1, SQL filter 21                     | none (generated)                   | ~7 lines of YAML, the MySQL switch in 3 files                  |
| **Symfony** (template) | entity 7                                                       | none (generated)                   | ~12 lines of YAML, the MySQL switch in 4 files, 1 template fix |
| **CakePHP**            | behaviors 4, price range 2, presence on update 2, login setup ~60 (service, middleware, component, 2 actions, password hashing, public pages), route 1 | login template 11, logout link 3, date inputs removed | MySQL compose ~13, datasource 4 |
| **CodeIgniter**        | migration ~14, model settings 6, controller ~40, routes 2      | 2 views (~55)                      | MySQL compose ~13, `.env` 9, 5 config lines (e-mail, redirects, CSRF) |

|                        | Composer packages (prod + dev) | Tests in the project |
|------------------------|--------------------------------|----------------------|
| **Gluo**               | 56 + 25                        | 49                   |
| **Laravel**            | 100 + 38                       | 47                   |
| **Symfony** (official) | 96 + 35                        | 8                    |
| **Symfony** (template) | 98 + 35                        | 8                    |
| **CakePHP**            | 22 + 71                        | 28                   |
| **CodeIgniter**        | 5 + 30                         | 17                   |

## Can the code be generated from the model or the table?

|                                                            | Direction                   | What the generator writes                                                      | What is left to write                                                  |
|------------------------------------------------------------|-----------------------------|--------------------------------------------------------------------------------|------------------------------------------------------------------------|
| **Gluo** `php gluo codegen:all`                              | table -> code               | working model, controller (get, list, create, update), tests, OpenAPI          | SQL migration, delete endpoint, frontend screen                        |
| **Symfony** `make:entity` + `make:crud` + `make:migration` | entity -> code and SQL      | entity with accessors, working controller, form, 6 Twig screens, migration SQL | validation, timestamps/soft delete wiring, access control, real tests  |
| **Laravel** `make:model -mfcr`                             | model -> migration -> table | empty skeletons (model, migration, factory, controller)                        | columns, rules, controller bodies, React screens, tests                |
| **CakePHP** `bake migration` + `bake all`                  | command line -> migration -> table -> code | migration, Table and Entity with validation, working controller, 4 styled templates, fixture, test placeholders | timestamps/soft delete wiring, price range, login, real tests |
| **CodeIgniter** `make:scaffold`                            | none; names the files       | empty skeletons (controller, model, migration, seeder)                         | migration, model settings, controller bodies, views, tests            |

Gluo and CakePHP start from an existing table and write working code from it; CakePHP's
bake also writes the screens. Symfony starts from the entity class and derives the SQL.
Laravel and CodeIgniter have no first-party generator that fills in a CRUD.

## Run them

Dependencies are not in the repository, so each project is installed first. From a
fresh clone:

```bash
# Gluo: API on :8080, frontend on :7080 and https://localhost:7443
cd gluo && composer install && docker compose up -d && php gluo --env=dev migrate:reset

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

# CakePHP: http://localhost:8084
cd cakephp && composer install && docker compose up -d --wait
bin/cake migrations migrate && bin/cake seeds run
bin/cake server -H 0.0.0.0 -p 8084

# CodeIgniter: http://localhost:8085
cd codeigniter && composer install && cp .env.example .env && docker compose up -d --wait
php spark migrate --all && php spark db:seed ProductSeeder
php spark serve --host 0.0.0.0 --port 8085
```

Log in with `admin@example.com` / `!P4ssw0rdstr!`. Each guide has the commands for the
tests and for shutting down.

The environment files in the repository hold generated development secrets and
placeholder credentials only. None of these projects is meant to be deployed as is.
