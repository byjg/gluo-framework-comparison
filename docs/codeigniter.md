---
sidebar_position: 70
title: Building it with CodeIgniter
---

# CodeIgniter

CodeIgniter 4.7 + its Model and Query Builder + Shield (the official authentication
package), with server-rendered views. CodeIgniter ships no Docker setup, so MySQL runs
from a hand-written compose file and PHP runs on the host.

Requirements: PHP >= 8.2 with `mysqli` and `intl`, Composer, Docker (for MySQL only).

## 1. Create the project and add Shield

```bash
composer create-project codeigniter4/appstarter codeigniter
cd codeigniter
composer require codeigniter4/shield
php spark shield:setup
```

`shield:setup` writes `app/Config/Auth.php`, `AuthGroups.php` and `AuthToken.php`, adds
the auth routes to `app/Config/Routes.php`, and turns on session-based CSRF in
`app/Config/Security.php`. It then asks for the sender e-mail address (set in step 3) and
whether to run its migrations (run in step 5). The login, registration, magic-link and
logout screens are now in place — nothing to write for LOGIN.

## 2. MySQL in Docker, and the environment

`compose.yaml`, written by hand:

```yaml
services:
  database:
    image: mysql:8.4
    environment:
      MYSQL_DATABASE: app
      MYSQL_ROOT_PASSWORD: password
    ports:
      - "3311:3306"
```

The environment comes from the `env` template the starter ships; this build keeps its
values in `.env.example`:

```dotenv
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8085/'
app.indexPage = ''
database.default.hostname = 127.0.0.1
database.default.database = app
database.default.username = root
database.default.password = password
database.default.DBDriver = MySQLi
database.default.port = 3311
```

`app.indexPage = ''` drops `index.php` from the URLs. The test suite does not use MySQL:
CodeIgniter's `tests` connection is SQLite in memory, and this build keeps that default.

## 3. Configuration edits

- `app/Config/Email.php` — `$fromEmail = 'noreply@example.com'`, `$fromName = 'Products'`
  (Shield sends the magic-link and password e-mails from it)
- `app/Config/Auth.php` — the `login` and `register` redirects go to `/products` instead
  of `/`
- `app/Config/Filters.php` — uncomment `'csrf'` in the global `before` filters. The
  starter ships it commented out; CodeIgniter's user guide recommends turning it on, and
  Laravel and Symfony have it on.

## 4. Generate the Product skeleton

```bash
php spark make:scaffold Product --restful presenter --table products --suffix
```

It writes four files — `app/Controllers/ProductController.php`,
`app/Models/ProductModel.php`, a migration and `app/Database/Seeds/ProductSeeder.php` —
and they are **empty**: the migration has empty `up()`/`down()`, the model has empty
`$allowedFields` and `$validationRules`, and the controller has one empty method per
action. CodeIgniter has no generator that reads a table.

## 5. Fill in the generated files

**Migration** — `app/Database/Migrations/*_ProductMigration.php`, with Forge:

```php
public function up()
{
    $this->forge->addField([
        'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
        'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
        'price'      => ['type' => 'DECIMAL', 'constraint' => '10,2'],
        'created_at' => ['type' => 'DATETIME', 'null' => true],
        'updated_at' => ['type' => 'DATETIME', 'null' => true],
        'deleted_at' => ['type' => 'DATETIME', 'null' => true],
    ]);
    $this->forge->addPrimaryKey('id');
    $this->forge->createTable('products');
}

public function down()
{
    $this->forge->dropTable('products');
}
```

**Model** — `app/Models/ProductModel.php`. Soft delete, timestamps and validation are
properties of the generated class; only their values change:

```php
protected $useSoftDeletes   = true;
protected $allowedFields    = ['name', 'price'];
protected $useTimestamps    = true;
protected $validationRules  = [
    'name'  => 'required|max_length[100]',
    'price' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[99999999.99]',
];
```

The model validates on every `insert()` and `update()`, and `delete()` sets `deleted_at`
instead of removing the row; `find()` and `paginate()` skip deleted rows.

**Controller** — `app/Controllers/ProductController.php`, a `ResourcePresenter`:

```php
protected $modelName = ProductModel::class;
protected $helpers = ['form'];

public function index()
{
    return view('products/index', [
        'products' => $this->model->orderBy('name')->paginate(20),
        'pager'    => $this->model->pager,
    ]);
}

public function create(): RedirectResponse
{
    if (! $this->model->insert($this->request->getPost(['name', 'price']))) {
        return redirect()->back()->withInput()->with('errors', $this->model->errors());
    }

    return redirect()->to('/products');
}
// + new(), edit(), update() and delete(), the same shape
```

**Routes** — `app/Config/Routes.php`. One line maps the presenter's actions, behind
Shield's `session` filter:

```php
$routes->get('/', static fn () => redirect()->to('/products'));
$routes->presenter('products', ['controller' => 'ProductController', 'except' => 'show,remove', 'filter' => 'session']);
```

**Views** — there is no generator for these; write them by hand, in plain HTML:

- `app/Views/products/index.php` — the table, Edit links, a Delete form per row, and
  `$pager->links()`
- `app/Views/products/form.php` — one form for both create and edit, with the validation
  errors and `old()` input

**Seeder** — `app/Database/Seeds/ProductSeeder.php` creates the admin user through
Shield's user provider, and five products:

```php
$users = auth()->getProvider();
$users->save(new User([
    'username' => 'admin',
    'email'    => 'admin@example.com',
    'password' => '!P4ssw0rdstr!',
]));
$users->findById($users->getInsertID())->addGroup('admin');
```

**Tests** — `tests/feature/ProductTest.php`.

## 6. Migrate, seed, test and run

```bash
cp .env.example .env
docker compose up -d --wait
php spark migrate --all
php spark db:seed ProductSeeder
vendor/bin/phpunit
php spark serve --host 0.0.0.0 --port 8085
```

`--all` runs Shield's migrations as well as the application's. Open
http://localhost:8085, log in with `admin@example.com` / `!P4ssw0rdstr!`.

Stop with Ctrl-C and `docker compose down` (add `-v` to drop the database volume).

## Screens and routes

| Method | Path | Result |
|---|---|---|
| GET/POST | `/login` | login screen (Shield) |
| GET | `/products` | list, 20 per page |
| GET / POST | `/products/new`, `/products/create` | new product |
| GET / POST | `/products/edit/{id}`, `/products/update/{id}` | edit product |
| POST | `/products/delete/{id}` | soft delete (sets `deleted_at`) |

Guests are redirected to `/login` on every product route.

## What was generated vs written

| | |
|---|---|
| Generated by commands | project, login, registration and magic-link screens (Shield), the empty skeletons of 4 files |
| Written by hand | the Docker file, the environment, 5 configuration lines, the migration, 4 model settings, 5 controller actions, 2 routes, 2 views, the seeder, the tests |

Like Laravel, CodeIgniter generates empty classes: `make:scaffold` names the files and the
base classes, and everything they contain is written by hand. What CodeIgniter adds
compared with Laravel is in the base `Model`: validation, timestamps, soft delete and
pagination are settings, not code.
