# Symfony — official packages only

Symfony 8.1 + Doctrine ORM + Twig + form login, using only what Symfony ships or pulls
in itself: `symfony/skeleton`, the `webapp` pack and the Docker service file that the
Flex recipe writes. No community Docker template and no community bundle.

The other Symfony build, [symfony.md](symfony.md), runs the same application on the
Symfony Docker template with a soft-delete bundle. The differences are listed in
[comparison.md](comparison.md#14-the-two-symfony-builds).

Requirements: PHP >= 8.4 with `pdo_mysql`, Composer, Docker (for MySQL only).

## 1. Create the project

```bash
composer create-project symfony/skeleton symfony-official
cd symfony-official
composer config extra.symfony.docker true
composer require webapp
composer require --dev orm-fixtures
```

With the Symfony CLI installed, the first four lines are `symfony new symfony-official --webapp`.

`extra.symfony.docker true` answers the question Flex asks ("include Docker
configuration from recipes?"), so the Doctrine recipe writes `compose.yaml` and
`compose.override.yaml` with a `database` service.

Every package is from `symfony/*`, or from Doctrine and Twig, which the `webapp` pack
itself requires.

## 2. Switch the database service to MySQL

The recipe writes a PostgreSQL service. Three edits:

- `compose.yaml` — the `database` service:

  ```yaml
  database:
    image: mysql:${MYSQL_VERSION:-8.4}
    environment:
      MYSQL_DATABASE: ${MYSQL_DATABASE:-app}
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD:-!ChangeMe!}
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 5s
      retries: 5
      start_period: 60s
    volumes:
      - database_data:/var/lib/mysql:rw
  ```

- `compose.override.yaml` — the published port: `"3309:3306"`
- `.env` — `DATABASE_URL="mysql://root:!ChangeMe!@127.0.0.1:3309/app?serverVersion=8.4&charset=utf8mb4"`

```bash
docker compose up -d --wait
```

Only MySQL (and the mail catcher the mailer recipe adds) runs in Docker. PHP runs on
the host.

## 3. Generate login and the Product CRUD

```bash
php bin/console make:user User --is-entity --identity-property-name=email --with-password
php bin/console make:security:form-login --controller-name=SecurityController \
    --user-class='App\Entity\User' --username-field=email --firewall-name=main --logout --with-tests
php bin/console make:entity Product --field=name:string:100 --field=price:decimal:10:2
php bin/console make:crud Product --with-tests
php bin/console make:entity Product --field=createdAt:datetime_immutable \
    --field=updatedAt:datetime_immutable --field='deletedAt:datetime_immutable?'
php bin/console make:fixtures AppFixtures
```

The order matters: `make:crud` builds the form and the pages from the fields the entity
has at that moment, so the three date fields are added **after** it. That keeps them
out of the form without editing it.

## 4. Hand edits

**Timestamps and validation** — `src/Entity/Product.php`. Doctrine has lifecycle
callbacks, so the dates need no extra package:

```php
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, max: 99999999.99)]
    private ?string $price = null;

    #[ORM\PrePersist]
    public function onCreate(): void
    {
        $this->createdAt = $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
```

**Soft delete** — Doctrine has no soft delete, so it is two pieces written by hand.

In `src/Controller/ProductController.php`, the generated `delete()` action sets the date
instead of removing the row:

```php
$product->setDeletedAt(new \DateTimeImmutable());
$entityManager->flush();
```

A Doctrine SQL filter hides deleted rows everywhere — `src/Doctrine/SoftDeleteFilter.php`:

```php
class SoftDeleteFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!$targetEntity->hasField('deletedAt')) {
            return '';
        }

        return sprintf('%s.%s IS NULL', $targetTableAlias, $targetEntity->getColumnName('deletedAt'));
    }
}
```

enabled in `config/packages/doctrine.yaml`, under `doctrine.orm`:

```yaml
        filters:
            soft_delete:
                class: App\Doctrine\SoftDeleteFilter
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
invalid data), so `tests/Controller/ProductControllerTest.php` was rewritten; it is the
same file as in the other Symfony build. In `tests/LoginControllerTest.php` only the
expected redirect changed (`/` -> `/product`).

## 5. Migrate, seed, test and run

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n

php bin/console --env=test doctrine:database:create --if-not-exists
php bin/console --env=test doctrine:migrations:migrate -n
php bin/phpunit

php -S 0.0.0.0:8083 -t public
```

Open http://localhost:8083/product and log in with `admin@example.com` /
`!P4ssw0rdstr!`.

`php -S` is PHP's built-in web server. With the Symfony CLI installed the last line is
`symfony server:start`, which Symfony's documentation describes as also picking up the
database address from Docker Compose. This build was run with `php -S` because the Symfony CLI
is not installed on the machine it was built on.

Stop MySQL with `docker compose down` (add `-v` to drop the database volume).

## Screens and routes

The same as the other Symfony build: `/login`, `/logout`, `/product`, `/product/new`,
`/product/{id}`, `/product/{id}/edit`, and `POST /product/{id}` to soft-delete. Guests
are redirected to `/login`. The generated pages are plain unstyled HTML.

## What was generated vs written

| | |
|---|---|
| Generated by commands | project, MySQL service file, user + login screen, entity with all six fields and accessors, repository, CRUD controller, form, 6 templates, migration SQL |
| Written by hand | 1 attribute + 2 callbacks + 4 validation attributes on the entity, 1 line in the delete action, the 21-line SQL filter, 2 small YAML blocks, the MySQL switch in 3 files, fixtures, tests |
