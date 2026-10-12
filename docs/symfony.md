---
sidebar_position: 50
title: Building it with Symfony (Docker template)
---

# Symfony — Docker template

Symfony 8.1 + Doctrine ORM + Twig + form login, on the
[Symfony Docker](https://github.com/dunglas/symfony-docker) template (FrankenPHP) with
MySQL 8.4.

Two pieces of this build are community packages, not part of Symfony: the Docker
template (the one Symfony's documentation points to for a complete Docker environment)
and `stof/doctrine-extensions-bundle` for timestamps and soft delete. The same
application built with official packages only is in
[symfony-official.md](symfony-official.md); the differences are listed in
[comparison.md](comparison.md#14-the-two-symfony-builds).

Requirements: Docker. PHP and Composer run inside the container.

## 1. Create the project

```bash
git clone --depth 1 https://github.com/dunglas/symfony-docker.git symfony
cd symfony
rm -rf .git
```

Two adjustments before the first start:

- In `compose.override.yaml`, replace `MERCURE_EXTRA_DIRECTIVES: demo` with
  `MERCURE_EXTRA_DIRECTIVES: protocol_version_compatibility 8`. With FrankenPHP 1.13 the
  template's Mercure block does not load otherwise and the container restarts in a loop
  (the error message itself asks for this directive).
- Choose the host ports. They go into `.env` later, because the first start overwrites
  that file — pass them on the command line for now.

```bash
docker compose build --pull --no-cache
SERVER_NAME=:80 HTTP_PORT=8082 HTTPS_PORT=8443 HTTP3_PORT=8443 docker compose up --wait
```

The first start runs `composer create-project symfony/skeleton` inside the container.
On Linux the files it creates belong to root; take them back (repeat after any command
that generates files):

```bash
docker compose exec php chown -R $(id -u):$(id -g) .
```

Append the ports to `.env` so later `docker compose` calls need no variables:

```dotenv
SERVER_NAME=:80
HTTP_PORT=8082
HTTPS_PORT=8443
HTTP3_PORT=8443
DB_PORT=3308
```

## 2. Install the full-stack packages

```bash
docker compose exec php composer config extra.symfony.allow-contrib true
docker compose exec php composer require webapp stof/doctrine-extensions-bundle
docker compose exec php composer require --dev orm-fixtures
```

`webapp` brings Twig, Doctrine, Security, Forms, Validator, MakerBundle and the test
pack. `stof/doctrine-extensions-bundle` provides timestamps and soft delete, which
Doctrine does not have built in.

## 3. Switch the database to MySQL

The Doctrine recipe adds a PostgreSQL service. Follow the template's own
`docs/mysql.md`:

- `compose.yaml` — the `database` service becomes `mysql:${MYSQL_VERSION:-8.4}` with
  `MYSQL_DATABASE` / `MYSQL_ROOT_PASSWORD`, a `mysqladmin ping` healthcheck and the
  volume on `/var/lib/mysql`; the `php` service gets
  `DATABASE_URL: mysql://root:${MYSQL_ROOT_PASSWORD:-!ChangeMe!}@database:3306/${MYSQL_DATABASE:-app}?serverVersion=${MYSQL_VERSION:-8.4}&charset=${MYSQL_CHARSET:-utf8mb4}`
- `compose.override.yaml` — the database port becomes `"${DB_PORT:-3306}:3306"`
- `Dockerfile` — `install-php-extensions pdo_mysql` instead of `pdo_pgsql`
- `.env` — `DATABASE_URL="mysql://root:!ChangeMe!@127.0.0.1:3308/app?serverVersion=8.4&charset=utf8mb4"`

The app connects as `root` (as the Gluo starter does) so it can also create the
`app_test` database for the tests.

```bash
docker compose up -d --build --wait
```

## 4. Generate login and the Product CRUD

```bash
docker compose exec php bin/console make:user User --is-entity --identity-property-name=email --with-password
docker compose exec php bin/console make:security:form-login --controller-name=SecurityController \
    --user-class='App\Entity\User' --username-field=email --firewall-name=main --logout --with-tests
docker compose exec php bin/console make:entity Product --field=name:string:100 --field=price:decimal:10:2
docker compose exec php bin/console make:crud Product --with-tests
docker compose exec php bin/console make:fixtures AppFixtures
```

Run without options, each command asks the same things interactively.

`make:crud` reads the entity and writes the **working** controller, form type and five
Twig templates (list, show, new, edit, delete) — the closest thing here to Gluo's
`codegen`, although it starts from the entity class and not from the table.

## 5. Hand edits

**Entity** — `src/Entity/Product.php`: timestamps, soft delete and validation.

```php
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[Gedmo\SoftDeleteable]
class Product
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, max: 99999999.99)]
    private ?string $price = null;
```

**Enable the extensions** — `config/packages/stof_doctrine_extensions.yaml`:

```yaml
stof_doctrine_extensions:
    default_locale: en_US
    orm:
        default:
            timestampable: true
            softdeleteable: true
```

and the filter that hides deleted rows, in `config/packages/doctrine.yaml` under
`doctrine.orm`:

```yaml
        filters:
            softdeleteable:
                class: Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter
                enabled: true
```

**Protect the screens** — `config/packages/security.yaml`:

```yaml
            form_login:
                login_path: app_login
                check_path: app_login
                enable_csrf: true
                default_target_path: app_product_index

    access_control:
        - { path: ^/login, roles: PUBLIC_ACCESS }
        - { path: ^/, roles: ROLE_USER }
```

**Seed** — `src/DataFixtures/AppFixtures.php` creates `admin@example.com` and five
products.

**Tests** — the tests written by `make:crud` are placeholders (`markTestIncomplete`,
invalid data), so `tests/Controller/ProductControllerTest.php` was rewritten. In
`tests/LoginControllerTest.php` only the expected redirect changed (`/` -> `/product`).

## 6. Migrate, seed and test

The migration SQL is generated by comparing the entities with the database:

```bash
docker compose exec php bin/console make:migration
docker compose exec php bin/console doctrine:migrations:migrate -n
docker compose exec php bin/console doctrine:fixtures:load -n

docker compose exec php bin/console --env=test doctrine:database:create --if-not-exists
docker compose exec php bin/console --env=test doctrine:migrations:migrate -n
docker compose exec php bin/phpunit
```

Open http://localhost:8082/product and log in with `admin@example.com` /
`!P4ssw0rdstr!`.

Stop with `docker compose down` (add `-v` to drop the database volume).

## Screens and routes

| Method | Path | Result |
|---|---|---|
| GET/POST | `/login`, GET `/logout` | login screen |
| GET | `/product` | list |
| GET/POST | `/product/new` | new product |
| GET | `/product/{id}` | show (with the Delete button) |
| GET/POST | `/product/{id}/edit` | edit product |
| POST | `/product/{id}` | soft delete (sets `deleted_at`) |

Guests are redirected to `/login`. The generated pages are plain unstyled HTML.

## What was generated vs written

| | |
|---|---|
| Generated by commands | project, Docker, user + login screen, entity with getters/setters, repository, CRUD controller, form, 6 templates, migration SQL |
| Written by hand | 2 traits + 1 attribute + 4 validation attributes on the entity, 3 small YAML blocks, the MySQL switch in the Docker files, fixtures, tests |
