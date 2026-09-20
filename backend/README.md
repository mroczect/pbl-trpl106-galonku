# Galonku Backend API

A REST API for running a water depot (depot air minum). Handles authentication, product and stock tracking, customer records, sales transactions, delivery schedules, and audit logging.

Plain PHP 8.1 with PDO. No framework. Three Composer packages: `vlucas/phpdotenv`, `firebase/php-jwt`, `monolog/monolog`.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database CLI](#database-cli)
- [Running the Server](#running-the-server)
- [Project Structure](#project-structure)
- [Architecture](#architecture)
- [Authentication](#authentication)
- [API Reference](#api-reference)
- [Stock Movements](#stock-movements)
- [Rate Limiting](#rate-limiting)
- [Database Schema](#database-schema)
- [Default Accounts](#default-accounts)
- [Testing](#testing)
- [Security Notes](#security-notes)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## Requirements

| Component | Version      |
| --------- | ------------ |
| PHP       | 8.1 or newer |
| Composer  | 2.x          |
| MySQL     | 8.0+         |
| MariaDB   | 10.5+        |

Required PHP extensions: `pdo`, `pdo_mysql`, `json`, `mbstring`, `openssl`.

Check them with:

```bash
php -m | grep -E 'pdo|json|mbstring|openssl'
```

---

## Installation

Clone the repo and install Composer dependencies:

```bash
git clone https://github.com/mroczect/pbl-trpl106-galonku
cd pbl-trpl106-galonku/backend/
composer install
```

Copy the environment templates:

```bash
cp .env.example .env
cp .env.testing.example .env.testing
```

Create the MySQL user (log in as admin first). The credentials below match the defaults in `.env.example`:

```sql
CREATE USER 'galonku'@'127.0.0.1' IDENTIFIED BY 'your_password_here';
CREATE USER 'galonku'@'localhost' IDENTIFIED BY 'your_password_here';

GRANT ALL PRIVILEGES ON galonku_db.*   TO 'galonku'@'127.0.0.1';
GRANT ALL PRIVILEGES ON galonku_db.*   TO 'galonku'@'localhost';
GRANT ALL PRIVILEGES ON galonku_test.* TO 'galonku'@'127.0.0.1';
GRANT ALL PRIVILEGES ON galonku_test.* TO 'galonku'@'localhost';

FLUSH PRIVILEGES;
```

Generate a JWT secret. The application refuses to start if the secret is shorter than 32 bytes:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Put the output in `JWT_SECRET` in both `.env` and `.env.testing`. They can be the same value; test isolation does not require different secrets, only different databases.

Build the schema and load seed data:

```bash
php database/db.php fresh --seed
```

Start the dev server:

```bash
composer serve
```

The API is now at `http://localhost:8000`.

---

## Configuration

Everything is read from `.env`. There is no config cache. `public/index.php` and `database/db.php` both load the file with `Dotenv::createImmutable()` at boot.

### Application

| Variable       | Default        | Notes                                                   |
| -------------- | -------------- | ------------------------------------------------------- |
| `APP_NAME`     | `Galonku API`  | Returned by the health check endpoint                   |
| `APP_ENV`      | `production`   | `local`, `testing`, or `production`                     |
| `APP_DEBUG`    | `false`        | When `true`, error responses include exception messages |
| `APP_TIMEZONE` | `Asia/Jakarta` | Passed to `date_default_timezone_set()`                 |

### Database

| Variable      | Default      | Notes              |
| ------------- | ------------ | ------------------ |
| `DB_HOST`     | `127.0.0.1`  | MySQL host         |
| `DB_PORT`     | `3306`       | MySQL port         |
| `DB_DATABASE` | `galonku_db` | Database name      |
| `DB_USERNAME` | `root`       | Database user      |
| `DB_PASSWORD` | _(empty)_    | Database password  |
| `DB_CHARSET`  | `utf8mb4`    | Connection charset |

### JWT

| Variable             | Default   | Notes                                                   |
| -------------------- | --------- | ------------------------------------------------------- |
| `JWT_SECRET`         | –         | HMAC key. Minimum 32 bytes. Refuses to boot below this. |
| `JWT_ACCESS_EXPIRE`  | `3600`    | Access token lifetime in seconds                        |
| `JWT_REFRESH_EXPIRE` | `2592000` | Refresh token lifetime in seconds (30 days)             |

### CORS and Rate Limiting

| Variable               | Default | Notes                                                    |
| ---------------------- | ------- | -------------------------------------------------------- |
| `CORS_ALLOWED_ORIGINS` | `*`     | Comma-separated origins, or `*`                          |
| `RATE_LIMIT_LOGIN`     | `5`     | Login attempts per window per IP                         |
| `RATE_LIMIT_WINDOW`    | `60`    | Window length in seconds                                 |
| `TRUSTED_PROXIES`      | –       | Comma-separated proxy IPs for `X-Forwarded-For` handling |

### Seeding in Production

The `UserSeeder` refuses to run when `APP_ENV=production` unless `SEED_ADMIN_PASSWORD` is set. `SEED_AGENT_PASSWORD` and `SEED_CUSTOMER_PASSWORD` are optional overrides for the other two demo accounts.

### Testing Environment

`.env.testing` is loaded when the shell has `APP_ENV=testing`. Point it at a separate database (`galonku_test`) so the test suite never touches development data. Both files need `JWT_SECRET` and database credentials.

---

## Database CLI

Every schema and seed operation goes through `php database/db.php`. The script reads `APP_ENV` from the shell to determine which env file to load — this is not read from `$_ENV`, so set it on the command line if you want tests.

```
Usage: php database/db.php <command> [options]
```

| Command          | What it does                                         |
| ---------------- | ---------------------------------------------------- |
| `migrate`        | Run all pending migrations                           |
| `rollback [n]`   | Roll back the last `n` batches (default `1`)         |
| `fresh [--seed]` | Drop every table, re-migrate, optionally seed        |
| `reset [--seed]` | Roll back all batches, re-migrate, optionally seed   |
| `seed [name]`    | Run all seeders, or a single one by class name       |
| `status`         | Show which migrations and seeders exist and what ran |
| `drop --force`   | Drop the entire database. Needs the `--force` flag.  |
| `help`           | Print usage                                          |

Examples:

```bash
php database/db.php migrate
php database/db.php fresh --seed
php database/db.php seed RoleSeeder
php database/db.php status
APP_ENV=testing php database/db.php fresh --seed
```

Composer shortcuts:

```bash
composer db:migrate
composer db:fresh
composer db:reset
composer db:seed
composer db:status
composer db:rollback
composer db:test:fresh
```

### Migrations

Each file in `database/migrations/` returns an anonymous class:

```php
return new class extends Migration {
    public function up(PDO $pdo): void { /* create tables */ }
    public function down(PDO $pdo): void { /* drop tables */ }
};
```

Filename format is `<YYYY_MM_DD_HHMMSS>_<description>.php`. Files starting with an underscore are treated as base classes and skipped. The base `Migration` class exposes `createTable()`, `dropTable()`, and `hasTable()` helpers; `createTable()` is idempotent (checks `information_schema.tables` before issuing `CREATE TABLE`).

Note: MySQL and MariaDB auto-commit DDL, so migrations do not run inside a transaction. If a migration fails halfway, the migrator halts and prints the error — you inspect the state manually.

### Seeders

Each file in `database/seeders/` returns an anonymous class extending `Seeder` with a `run(PDO $pdo)` method and a `priority(): int` method. Lower priority runs first. Current order:

| Priority | Seeder           |
| -------- | ---------------- |
| 10       | `RoleSeeder`     |
| 20       | `UserSeeder`     |
| 30       | `ProductSeeder`  |
| 40       | `CustomerSeeder` |
| 50       | `DemoSeeder`     |

The order respects foreign keys. Seeders use `ON DUPLICATE KEY UPDATE`, so running them repeatedly is safe. To add a new seeder, drop the file in `database/seeders/` and give it a priority number — no registry to update.

---

## Running the Server

Development:

```bash
composer serve
```

This runs `php -S localhost:8000 -t public`. Every request lands on `public/index.php`.

For production, point Nginx/Apache/Caddy at the `public/` directory. Only `public/index.php` should be reachable; everything else is loaded via the autoloader.

Nginx example:

```nginx
server {
    listen 80;
    server_name api.example.com;
    root /var/www/galonku-backend/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

---

## Project Structure

```
backend/
├── app/
│   ├── Controllers/Api/V1/       One controller per resource
│   ├── Core/                     Framework primitives
│   │   ├── App.php               Bootstrap: error handler, CORS, router
│   │   ├── Auth.php              Auth state, hashing, login helpers
│   │   ├── Database.php          PDO singleton, transaction wrapper
│   │   ├── Model.php             Base model with query helpers
│   │   ├── Request.php           Request wrapper
│   │   ├── Response.php          JSON response helper
│   │   ├── Router.php            Route registry and dispatcher
│   │   └── Validator.php         Input validation
│   ├── Exceptions/               ValidationException, AuthException, NotFoundException, ErrorHandler
│   ├── Middleware/               AuthMiddleware, RoleMiddleware, RateLimitMiddleware
│   ├── Models/                   User, Product, Customer, Transaction, Schedule, Role, Log, StockMovement
│   ├── Services/                 AuthService, TransactionService, StockService
│   ├── Support/                  Jwt, AppLogger, helpers.php
│   └── Testing/                  ResponseCaptured for tests
├── config/
│   ├── cors.php
│   └── database.php
├── public/
│   └── index.php                 Single entry point
├── routes/
│   └── api.php                   Route definitions
├── storage/
│   ├── cache/                    Rate-limit JSON files
│   ├── logs/                     Monolog output (app.log)
│   └── uploads/                  Reserved for file uploads
├── tests/
│   ├── Feature/                  HTTP endpoint tests
│   ├── Integration/              Multi-step workflow tests
│   ├── Unit/                     Isolated component tests
│   ├── Support/                  Test infrastructure
│   └── bootstrap.php
├── composer.json
├── phpunit.xml
├── .env.example
├── .env.testing.example
└── README.md
```

The migration and seeder engine lives in a sibling directory (`../database/`) alongside the backend.

---

## Architecture

### Request Lifecycle

1. The web server forwards every request to `public/index.php`.
2. Dotenv loads `.env` (or `.env.testing` when `APP_ENV=testing`).
3. `App::boot()` registers the exception handler, security headers, and CORS headers. It builds a router and loads `routes/api.php`.
4. `App::run()` captures the incoming request with `Request::capture()` and hands it to `Router::dispatch()`.
5. The router matches path and method. On a match it runs middleware in order, then calls the controller action.
6. The controller talks to services and models, then emits a response through `Response::success()` or `Response::error()`. Both call `exit` after sending JSON.
7. Any uncaught exception reaches `ErrorHandler::handle()`, which maps exception types to HTTP codes and logs everything else to `storage/logs/app.log` through Monolog.

### Routing

Register routes with `$router->get()`, `->post()`, `->put()`, `->patch()`, `->delete()`. Path parameters use `{name}` and are passed to the controller after the `Request` argument:

```php
$router->get('/products/{id}', [ProductController::class, 'show']);
// Controller signature: show(Request $req, int $id)
```

Groups apply a shared prefix and middleware list:

```php
$router->group(['prefix' => '/api/v1', 'middleware' => [AuthMiddleware::class]], function ($router) {
    // ...
});
```

Middleware is referenced by class name, with optional arguments after a colon:

```php
[RoleMiddleware::class . ':administrator']
[RateLimitMiddleware::class . ':login:5:60']
```

### Response Contract

Every response is JSON.

Success:

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": {}
}
```

Error:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {}
}
```

`meta` only appears on paginated endpoints. `errors` only appears on validation failures and typically maps field names to arrays of messages.

### Error Handling

`ErrorHandler::handle()` maps exceptions:

| Exception                                        | HTTP status |
| ------------------------------------------------ | ----------- |
| `ValidationException`                            | 422         |
| `AuthException`                                  | 401         |
| `NotFoundException`                              | 404         |
| `InvalidArgumentException`                       | 400         |
| `PDOException` (SQLSTATE 23000, dup entry)       | 409         |
| `PDOException` (SQLSTATE 23000, FK violation)    | 422         |
| `PDOException` (SQLSTATE 23000, CHECK violation) | 422         |
| `PDOException` (other)                           | 500         |
| Any other exception                              | 500         |

Unhandled exceptions go to `storage/logs/app.log` with file, line, and stack trace. When `APP_DEBUG=true`, level-500 responses include the exception message. In production they return a generic `Internal server error`.

### Validation

Rules are declared per endpoint as a `field => rules` map. Rule strings separate options with `|` and arguments with `:`:

```php
$data = $req->validate([
    'name'     => 'required|min:3|max:100',
    'email'    => 'required|email|max:150',
    'password' => 'required|min:6|max:100',
    'phone'    => 'phone',
]);
```

Supported rules: `required`, `email`, `min`, `max`, `numeric`, `integer`, `in`, `array`, `date`, `boolean`, `phone`.

`Validator::make()` returns only the fields that pass and are present. Absent or null fields that aren't `required` are dropped from the result. Any failure throws `ValidationException` carrying per-field errors.

---

## Authentication

JWT, HS256 signatures.

### Token Types

Two tokens are issued at login:

- **Access token** — short-lived (default 1 hour). Sent in the `Authorization` header on protected requests. Claims: `sub` (user ID), `email`, `role`, `typ=access`, `jti`, `iat`, `exp`.
- **Refresh token** — long-lived (default 30 days). Only used at `POST /api/v1/auth/refresh`. Claims: `sub`, `typ=refresh`, `jti`, `iat`, `exp`. No role or email.

Both carry a `jti` claim — a 32-character hex string. `jti` is what gets blacklisted.

### Protected Requests

```
Authorization: Bearer <access_token>
```

`AuthMiddleware` reads the token, verifies the signature, ensures `typ=access`, checks the `jti` against the blacklist, and loads the user from the database. Any failure returns 401.

### Refresh Flow

```
POST /api/v1/auth/refresh
{ "refresh_token": "<token>" }
```

The endpoint verifies the refresh token, confirms the user still exists and is active, then issues a new token pair. The old refresh token is blacklisted at the same time, so it cannot be reused.

### Logout

```
POST /api/v1/auth/logout
Authorization: Bearer <access_token>
```

Inserts the current access token's `jti` into `jwt_blacklist` with its expiry. If the body carries `refresh_token`, that `jti` is blacklisted too.

Blacklist rows expire naturally — set up a cron to clean up:

```sql
DELETE FROM jwt_blacklist WHERE expires_at < NOW();
```

`database/cleanup.php` does this along with pruning old rate-limit cache files:

```bash
php database/cleanup.php
```

### Password Hashing

`Auth::hash()` uses Argon2id when available, falling back to Bcrypt. Hashes are never returned in responses — controllers strip `password_hash` before serializing user records.

---

## API Reference

Base URL: `/api/v1`. All requests and responses use `Content-Type: application/json`.

### Response Format

Paginated endpoints accept `page` and `per_page`. `per_page` is capped at 100. The response includes:

```json
{
  "success": true,
  "message": "OK",
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 42,
    "total_pages": 3
  }
}
```

### Error Codes

| Status | Meaning                                |
| ------ | -------------------------------------- |
| 200    | Success                                |
| 201    | Resource created                       |
| 400    | Bad request, invalid argument          |
| 401    | Missing or invalid credentials         |
| 403    | Authenticated but not authorized       |
| 404    | Resource not found                     |
| 405    | Method not allowed for this path       |
| 409    | Conflict (usually a unique constraint) |
| 422    | Validation failed                      |
| 429    | Rate limit exceeded                    |
| 500    | Unhandled server error                 |

---

### Auth Endpoints

#### Register

```
POST /api/v1/auth/register
```

Public. Creates a user with the `customer` role. Rate-limited at 5 requests per hour per IP.

Body:

| Field      | Type   | Required | Rules                              |
| ---------- | ------ | -------- | ---------------------------------- |
| `name`     | string | yes      | 3–100 characters                   |
| `email`    | string | yes      | Valid email, max 150 chars, unique |
| `password` | string | yes      | 6–100 characters                   |
| `phone`    | string | no       | 8–20 digits, `+`, `-`, or spaces   |

Example:

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "password": "secret123",
    "phone": "081234567890"
  }'
```

Response `201`:

```json
{ "success": true, "message": "Registration successful", "data": { "id": 4 } }
```

Returns `409` if the email already exists. Returns `422` on validation failure.

---

#### Login

```
POST /api/v1/auth/login
```

Public. Rate-limited at 5 requests per 60 seconds per IP. Success updates `users.last_login_at` and writes to `logs`.

Body: `email`, `password`.

Example:

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@galonku.com", "password": "admin123"}'
```

Response `200`:

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Administrator",
      "email": "admin@galonku.com",
      "phone": "081234567890",
      "role_id": 1,
      "role_name": "administrator"
    },
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

Returns `401` on wrong credentials or an inactive account. Returns `429` if the rate limit is exceeded.

---

#### Refresh Token

```
POST /api/v1/auth/refresh
```

Public. Rate-limited at 20 requests per minute per IP. Exchanges a valid refresh token for a new pair. The old refresh token is blacklisted.

Body: `refresh_token`.

Response `200`:

```json
{
  "success": true,
  "message": "Token refreshed",
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

Returns `401` if the token is malformed, expired, blacklisted, or the wrong type. Access tokens are rejected here.

---

#### Current User

```
GET /api/v1/auth/me
Authorization: Bearer <token>
```

Returns the authenticated user's record with `password_hash` removed.

Response `200`:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 1,
    "role_id": 1,
    "name": "Administrator",
    "email": "admin@galonku.com",
    "phone": "081234567890",
    "is_active": 1,
    "last_login_at": "2026-09-17 04:21:33",
    "created_at": "2026-09-17 04:00:00",
    "updated_at": "2026-09-17 04:21:33",
    "role_name": "administrator"
  }
}
```

Returns `401` if the token is missing, invalid, expired, or blacklisted.

---

#### Logout

```
POST /api/v1/auth/logout
Authorization: Bearer <token>
```

Blacklists the current access token (and the refresh token in the body if present). Writes a `logout` action to `logs`.

Response `200`:

```json
{ "success": true, "message": "Logged out successfully", "data": null }
```

---

### Product Endpoints

Products have a SKU, name, category, price, stock, and active flag. Categories: `galon`, `air`, `aksesoris`, `lain`.

#### List Products

```
GET /api/v1/products?page=1&per_page=15&category=galon
Authorization: Bearer <token>
```

Any authenticated user. `category` is optional.

Response `200`:

```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 1,
      "sku": "GLN-AQUA-19L",
      "name": "Galon Aqua 19L",
      "category": "galon",
      "price": "20000.00",
      "stock": 50,
      "is_active": 1,
      "created_at": "2026-09-17 04:00:00",
      "updated_at": "2026-09-17 04:00:00"
    }
  ],
  "meta": { "page": 1, "per_page": 15, "total": 5, "total_pages": 1 }
}
```

---

#### Get Product

```
GET /api/v1/products/{id}
Authorization: Bearer <token>
```

Any authenticated user. Returns `404` if not found.

---

#### Low-Stock Products

```
GET /api/v1/products/low-stock?threshold=10
Authorization: Bearer <token>
```

Any authenticated user. Returns active products with `stock <= threshold`. Default threshold is 10.

---

#### Create Product

```
POST /api/v1/products
Authorization: Bearer <token>
Role: administrator
```

Body:

| Field      | Type    | Required | Rules                                      |
| ---------- | ------- | -------- | ------------------------------------------ |
| `sku`      | string  | yes      | 3–50 characters, unique                    |
| `name`     | string  | yes      | 3–150 characters                           |
| `price`    | numeric | yes      | –                                          |
| `category` | string  | no       | One of `galon`, `air`, `aksesoris`, `lain` |
| `stock`    | integer | no       | Default 0                                  |

Example:

```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "sku": "GLN-NEW-01",
    "name": "Galon Baru 19L",
    "price": 22000,
    "category": "galon",
    "stock": 25
  }'
```

Response `201`: `{ "success": true, "message": "Product created", "data": { "id": 6 } }`

Returns `409` on duplicate SKU, `422` on validation failure, `403` for non-admins.

Note: creating a product with an initial `stock` value does **not** write a `stock_movements` row. If you want a paper trail, create the product with `stock: 0` and then use the stock adjustment endpoint.

---

#### Update Product

```
PUT /api/v1/products/{id}
Authorization: Bearer <token>
Role: administrator
```

All fields optional. Accepts `name`, `category`, `price`, `stock`, `is_active`. Only the fields you send are updated.

Returning `400` with `No data to update` means you sent an empty body.

Warning: sending `stock` here overwrites the current value **without** recording a stock movement. Use `POST /api/v1/stock-movements/adjust` when you want an audit trail.

Returns `404` if the product does not exist.

---

#### Delete Product

```
DELETE /api/v1/products/{id}
Authorization: Bearer <token>
Role: administrator
```

Soft delete: sets `is_active = 0`. The row stays in the database so transaction items referencing it still resolve.

Response `200`: `{ "success": true, "message": "Product deactivated", "data": null }`

---

### Stock Movements

Every change to `products.stock` that goes through `StockService` writes a row to `stock_movements`. The table records who changed what, before/after values, and why.

Movement types:

- `in` — stock added (manual restock, purchase delivery)
- `out` — stock removed (sale, delivery, damage)
- `adjustment` — correction to a specific target value

`reference_type` and `reference_id` link a movement to a source record (e.g. `transaction` + ID 42). For manual entries they are null.

#### List Stock Movements

```
GET /api/v1/stock-movements?page=1&per_page=30&product_id=1&type=out&from=2026-09-01&to=2026-09-30
Authorization: Bearer <token>
```

Any authenticated user. All filters optional.

| Query param  | Notes                                                     |
| ------------ | --------------------------------------------------------- |
| `product_id` | Filter by product                                         |
| `type`       | `in`, `out`, or `adjustment`                              |
| `user_id`    | Filter by who performed the movement                      |
| `from`       | Inclusive start date (`YYYY-MM-DD`), matches `created_at` |
| `to`         | Inclusive end date (`YYYY-MM-DD`)                         |

Response `200`:

```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 7,
      "product_id": 1,
      "user_id": 1,
      "type": "out",
      "qty": 2,
      "stock_before": 50,
      "stock_after": 48,
      "reason": "Penjualan",
      "reference_type": "transaction",
      "reference_id": 4,
      "notes": null,
      "created_at": "2026-09-17 04:30:00",
      "product_name": "Galon Aqua 19L",
      "sku": "GLN-AQUA-19L",
      "user_name": "Administrator"
    }
  ],
  "meta": { "page": 1, "per_page": 30, "total": 1, "total_pages": 1 }
}
```

---

#### Adjust Stock

```
POST /api/v1/stock-movements/adjust
Authorization: Bearer <token>
Role: administrator
```

Sets a product's stock to an exact target value and records the difference as an `adjustment` movement. Use this for stock opname corrections, damaged goods write-offs, or fixing a miscount.

Body:

| Field        | Type    | Required | Rules                    |
| ------------ | ------- | -------- | ------------------------ |
| `product_id` | integer | yes      | Must reference a product |
| `new_stock`  | integer | yes      | Minimum 0                |
| `reason`     | string  | yes      | 3–100 characters         |
| `notes`      | string  | no       | Up to 500 characters     |

Example:

```bash
curl -X POST http://localhost:8000/api/v1/stock-movements/adjust \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "new_stock": 45,
    "reason": "Stock opname September",
    "notes": "3 galon bocor, 2 tidak ditemukan"
  }'
```

Response `200`:

```json
{
  "success": true,
  "message": "Stock adjusted",
  "data": {
    "before": 48,
    "after": 45,
    "changed": true
  }
}
```

If `new_stock` equals the current stock, `changed` is `false` and no movement row is written.

The whole operation runs inside a database transaction with a `SELECT ... FOR UPDATE` lock on the product row.

---

### Customer Endpoints

#### List Customers

```
GET /api/v1/customers?page=1&per_page=15
Authorization: Bearer <token>
```

Any authenticated user. Ordered by ID descending.

---

#### Get Customer

```
GET /api/v1/customers/{id}
Authorization: Bearer <token>
```

Returns `404` if not found.

---

#### Create Customer

```
POST /api/v1/customers
Authorization: Bearer <token>
```

Any authenticated user.

Body:

| Field     | Type   | Required | Rules                                    |
| --------- | ------ | -------- | ---------------------------------------- |
| `name`    | string | yes      | 3–100 characters                         |
| `phone`   | string | yes      | 8–20 digits, `+`, `-`, or spaces, unique |
| `address` | string | no       | Free text                                |
| `notes`   | string | no       | Free text                                |

Response `201`: `{ "success": true, "message": "Customer created", "data": { "id": 4 } }`

Returns `409` on duplicate phone.

---

#### Update Customer

```
PUT /api/v1/customers/{id}
Authorization: Bearer <token>
```

Accepts `name`, `phone`, `address`, `notes`, `is_active`.

---

#### Delete Customer

```
DELETE /api/v1/customers/{id}
Authorization: Bearer <token>
Role: administrator
```

Soft delete: sets `is_active = 0`.

---

### Transaction Endpoints

A transaction has a header (`transactions`) and one or more items (`transaction_items`). Creating one deducts product stock atomically inside a database transaction. If any item fails (insufficient stock, unknown product, bad quantity), the whole thing rolls back — no header, no items, no stock change, no movement rows.

Every successful item write also inserts a `stock_movements` row with `type = 'out'` and `reference_type = 'transaction'`.

#### List Transactions

```
GET /api/v1/transactions?page=1&per_page=15&status=paid&customer_id=1&user_id=1&type=sale
Authorization: Bearer <token>
```

Any authenticated user. Filters are optional. Returns headers with joined customer and user names.

---

#### Get Transaction

```
GET /api/v1/transactions/{id}
Authorization: Bearer <token>
```

Returns the header with joined relations and the full item list.

Response `200`:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 1,
    "invoice_no": "INV-20260917-8C4A",
    "customer_id": 1,
    "user_id": 1,
    "type": "sale",
    "total_amount": "26000.00",
    "paid_amount": "26000.00",
    "status": "paid",
    "notes": null,
    "created_at": "2026-09-17 04:30:00",
    "updated_at": "2026-09-17 04:30:00",
    "customer_name": "Ibu Siti",
    "customer_phone": "081111222333",
    "user_name": "Administrator",
    "items": [
      {
        "id": 1,
        "transaction_id": 1,
        "product_id": 1,
        "qty": 1,
        "unit_price": "20000.00",
        "subtotal": "20000.00",
        "product_name": "Galon Aqua 19L",
        "sku": "GLN-AQUA-19L"
      }
    ]
  }
}
```

---

#### Create Transaction

```
POST /api/v1/transactions
Authorization: Bearer <token>
```

Any authenticated user. Runs inside a single database transaction with `SELECT ... FOR UPDATE` locks on every product row involved.

Body:

| Field         | Type    | Required | Rules                                                         |
| ------------- | ------- | -------- | ------------------------------------------------------------- |
| `customer_id` | integer | yes      | Must exist and be active                                      |
| `items`       | array   | yes      | Non-empty array of `{ "product_id": int, "qty": int }`        |
| `type`        | string  | no       | `sale`, `delivery`, or `return`. Default `sale`.              |
| `paid_amount` | numeric | no       | Default 0                                                     |
| `status`      | string  | no       | `pending`, `paid`, `partial`, `cancelled`. Default `pending`. |
| `notes`       | string  | no       | –                                                             |

Example:

```bash
curl -X POST http://localhost:8000/api/v1/transactions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1,
    "type": "sale",
    "items": [
      { "product_id": 1, "qty": 2 },
      { "product_id": 2, "qty": 1 }
    ]
  }'
```

The service:

1. Verifies the customer exists and is active.
2. Aggregates items by `product_id` (so sending `{product 1, qty 2}` twice collapses into `qty 4`).
3. Generates an invoice number in the form `INV-YYYYMMDD-XXXXXXXXXXXXXXXX` — eight hex bytes, uppercase.
4. Iterates aggregated items, locks each product row with `FOR UPDATE`, and checks stock.
5. Computes subtotals and total.
6. Inserts the transaction header.
7. Inserts each item, decrements stock, and writes a `stock_movements` row.
8. Writes an audit log entry.
9. Commits.

Response `201`:

```json
{
  "success": true,
  "message": "Transaction created successfully",
  "data": {
    "id": 1,
    "invoice_no": "INV-20260917-8C4A2F9D",
    "total_amount": 46000,
    "items_count": 2
  }
}
```

Errors:

- `422` if `customer_id` or `items` is missing/invalid, or `paid_amount > total_amount`.
- `404` if a referenced product does not exist.
- `422` with message `Insufficient stock` if stock is short (message includes the product name when `APP_DEBUG=true`).

---

#### Update Transaction Status

```
PUT /api/v1/transactions/{id}/status
Authorization: Bearer <token>
```

Any authenticated user.

Body:

| Field         | Type    | Required | Rules                                        |
| ------------- | ------- | -------- | -------------------------------------------- |
| `status`      | string  | yes      | `pending`, `paid`, `partial`, or `cancelled` |
| `paid_amount` | numeric | no       | Must not exceed `total_amount`               |

Response `200`: `{ "success": true, "message": "Transaction status updated", "data": null }`

---

### Schedule Endpoints

Schedules assign a customer to a courier (a user) at a future time.

#### List Schedules

```
GET /api/v1/schedules?page=1&per_page=15&status=pending&user_id=2&customer_id=1
Authorization: Bearer <token>
```

Any authenticated user. All filters optional. Ordered by `scheduled_at` descending. Response includes joined `customer_name` and `user_name`.

---

#### Get Schedule

```
GET /api/v1/schedules/{id}
Authorization: Bearer <token>
```

Returns `404` if not found.

---

#### Create Schedule

```
POST /api/v1/schedules
Authorization: Bearer <token>
```

Body:

| Field          | Type    | Required | Rules                                        |
| -------------- | ------- | -------- | -------------------------------------------- |
| `customer_id`  | integer | yes      | Must exist and be active                     |
| `user_id`      | integer | yes      | Must exist and be active                     |
| `scheduled_at` | string  | yes      | `YYYY-MM-DD HH:MM:SS`, must be in the future |
| `notes`        | string  | no       | –                                            |

New schedules default to `pending`.

Response `201`: `{ "success": true, "message": "Schedule created", "data": { "id": 1 } }`

Returns `422` if the datetime format is wrong or the time is not in the future. Returns `404` if the customer or user is missing/inactive.

---

#### Update Schedule Status

```
PUT /api/v1/schedules/{id}/status
Authorization: Bearer <token>
```

Body:

| Field    | Type   | Required | Rules                                         |
| -------- | ------ | -------- | --------------------------------------------- |
| `status` | string | yes      | `pending`, `on_route`, `done`, or `cancelled` |

---

### User Endpoints

All endpoints here require the `administrator` role.

#### List Users

```
GET /api/v1/users?page=1&per_page=15
Authorization: Bearer <token>
Role: administrator
```

Paginated list with joined role names.

---

#### Get User

```
GET /api/v1/users/{id}
Authorization: Bearer <token>
Role: administrator
```

User record with `password_hash` stripped.

---

#### Update User

```
PUT /api/v1/users/{id}
Authorization: Bearer <token>
Role: administrator
```

Accepts `name`, `phone`, `role_id`, `is_active`. Setting `role_id` validates that the role exists — you get `422` if it doesn't.

---

#### Delete User

```
DELETE /api/v1/users/{id}
Authorization: Bearer <token>
Role: administrator
```

Soft delete: sets `is_active = 0`. The user can still log in until their access token expires; new logins are refused because `Auth::attempt()` checks `is_active`.

---

### Role Endpoints

```
GET /api/v1/roles
Authorization: Bearer <token>
Role: administrator
```

Returns all roles.

Response `200`:

```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 1,
      "name": "administrator",
      "description": "System administrator with full access",
      "created_at": "...",
      "updated_at": "..."
    },
    {
      "id": 2,
      "name": "agent",
      "description": "Field agent handling deliveries",
      "created_at": "...",
      "updated_at": "..."
    },
    {
      "id": 3,
      "name": "customer",
      "description": "Registered customer account",
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

---

### Log Endpoints

```
GET /api/v1/logs?limit=100
Authorization: Bearer <token>
Role: administrator
```

Returns the latest audit logs, joined with user names. `limit` is capped at 500. Default 100.

Response `200`:

```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 12,
      "user_id": 1,
      "action": "login",
      "entity": "user",
      "entity_id": 1,
      "payload": "{\"email\":\"admin@galonku.com\"}",
      "ip_address": "127.0.0.1",
      "created_at": "2026-09-17 04:30:00",
      "user_name": "Administrator"
    }
  ]
}
```

Recorded actions: `register`, `login`, `logout`, `create`, `update`, `delete`, each with an entity type and optional entity ID.

---

### Health Check

```
GET /
```

Public. Returns app status.

Response `200`:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "app": "Galonku API",
    "version": "1.0.0",
    "status": "running",
    "time": "2026-09-17T04:30:00+07:00"
  }
}
```

---

## Rate Limiting

Applied per endpoint via `RateLimitMiddleware`, declared in the route:

```php
$router->post('/auth/login', [AuthController::class, 'login'],
    [RateLimitMiddleware::class . ':login:5:60']);
```

Three arguments after the class name: identifier prefix, max requests, and window in seconds. The bucket key is `md5(ip:prefix)` — a client on IP A can't exhaust IP B's bucket, and each prefix (endpoint) has its own bucket per IP.

State lives in `storage/cache/rl_<hash>.json`. Writes are guarded by `flock(LOCK_EX)` so concurrent requests don't race. When the limit trips, the server returns `429` with a `Retry-After` header giving seconds to wait.

The cache directory must be writable by the PHP user. Clean stale files periodically:

```bash
find storage/cache -name 'rl_*.json' -mmin +60 -delete
```

Or run the bundled cleanup script:

```bash
php database/cleanup.php
```

---

## Database Schema

Eleven tables, all InnoDB, all `utf8mb4_unicode_ci` (except `migrations`, which uses the connection default).

### roles

| Column                 | Type         | Notes  |
| ---------------------- | ------------ | ------ |
| id                     | INT UNSIGNED | PK     |
| name                   | VARCHAR(50)  | Unique |
| description            | VARCHAR(255) |        |
| created_at, updated_at | TIMESTAMP    |        |

### users

| Column                 | Type         | Notes              |
| ---------------------- | ------------ | ------------------ |
| id                     | INT UNSIGNED | PK                 |
| role_id                | INT UNSIGNED | FK → roles.id      |
| name                   | VARCHAR(100) |                    |
| email                  | VARCHAR(150) | Unique             |
| phone                  | VARCHAR(20)  |                    |
| password_hash          | VARCHAR(255) | Argon2id or Bcrypt |
| is_active              | TINYINT(1)   | Default 1          |
| last_login_at          | TIMESTAMP    | Nullable           |
| created_at, updated_at | TIMESTAMP    |                    |

Indexes on `role_id` and `is_active`.

### customers

| Column                 | Type         | Notes  |
| ---------------------- | ------------ | ------ |
| id                     | INT UNSIGNED | PK     |
| name                   | VARCHAR(100) |        |
| phone                  | VARCHAR(20)  | Unique |
| address                | TEXT         |        |
| notes                  | TEXT         |        |
| is_active              | TINYINT(1)   |        |
| created_at, updated_at | TIMESTAMP    |        |

Indexes on `is_active` and `name`.

### products

| Column                 | Type          | Notes                               |
| ---------------------- | ------------- | ----------------------------------- |
| id                     | INT UNSIGNED  | PK                                  |
| sku                    | VARCHAR(50)   | Unique                              |
| name                   | VARCHAR(150)  |                                     |
| category               | ENUM          | `galon`, `air`, `aksesoris`, `lain` |
| price                  | DECIMAL(12,2) |                                     |
| stock                  | INT           | CHECK `stock >= 0`                  |
| is_active              | TINYINT(1)    |                                     |
| created_at, updated_at | TIMESTAMP     |                                     |

Indexes on `category`, `is_active`, `stock`.

### transactions

| Column                 | Type          | Notes                                     |
| ---------------------- | ------------- | ----------------------------------------- |
| id                     | INT UNSIGNED  | PK                                        |
| invoice_no             | VARCHAR(30)   | Unique                                    |
| customer_id            | INT UNSIGNED  | FK → customers.id                         |
| user_id                | INT UNSIGNED  | FK → users.id                             |
| type                   | ENUM          | `sale`, `delivery`, `return`              |
| total_amount           | DECIMAL(14,2) |                                           |
| paid_amount            | DECIMAL(14,2) |                                           |
| status                 | ENUM          | `pending`, `paid`, `partial`, `cancelled` |
| notes                  | TEXT          |                                           |
| created_at, updated_at | TIMESTAMP     |                                           |

Indexes on `customer_id`, `user_id`, `status`, `type`, `created_at`.

### transaction_items

| Column         | Type          | Notes                                     |
| -------------- | ------------- | ----------------------------------------- |
| id             | INT UNSIGNED  | PK                                        |
| transaction_id | INT UNSIGNED  | FK → transactions.id, `ON DELETE CASCADE` |
| product_id     | INT UNSIGNED  | FK → products.id                          |
| qty            | INT           | CHECK `qty > 0`                           |
| unit_price     | DECIMAL(12,2) |                                           |
| subtotal       | DECIMAL(14,2) |                                           |

Indexes on `transaction_id` and `product_id`.

### schedules

| Column                 | Type         | Notes                                      |
| ---------------------- | ------------ | ------------------------------------------ |
| id                     | INT UNSIGNED | PK                                         |
| customer_id            | INT UNSIGNED | FK → customers.id                          |
| user_id                | INT UNSIGNED | FK → users.id                              |
| scheduled_at           | DATETIME     |                                            |
| status                 | ENUM         | `pending`, `on_route`, `done`, `cancelled` |
| notes                  | TEXT         |                                            |
| created_at, updated_at | TIMESTAMP    |                                            |

Indexes on `customer_id`, `user_id`, `status`, `scheduled_at`.

### stock_movements

| Column         | Type         | Notes                                         |
| -------------- | ------------ | --------------------------------------------- |
| id             | INT UNSIGNED | PK                                            |
| product_id     | INT UNSIGNED | FK → products.id                              |
| user_id        | INT UNSIGNED | FK → users.id, nullable                       |
| type           | ENUM         | `in`, `out`, `adjustment`                     |
| qty            | INT          | Always positive; the type indicates direction |
| stock_before   | INT          |                                               |
| stock_after    | INT          |                                               |
| reason         | VARCHAR(255) | Short description                             |
| reference_type | VARCHAR(50)  | Nullable, e.g. `transaction`                  |
| reference_id   | INT UNSIGNED | Nullable                                      |
| notes          | TEXT         | Nullable                                      |
| created_at     | TIMESTAMP    |                                               |

Indexes on `product_id`, `user_id`, `type`, `(reference_type, reference_id)`, `created_at`.

### logs

| Column     | Type            | Notes                               |
| ---------- | --------------- | ----------------------------------- |
| id         | BIGINT UNSIGNED | PK                                  |
| user_id    | INT UNSIGNED    | FK → users.id, `ON DELETE SET NULL` |
| action     | VARCHAR(100)    |                                     |
| entity     | VARCHAR(100)    |                                     |
| entity_id  | INT UNSIGNED    |                                     |
| payload    | JSON            |                                     |
| ip_address | VARCHAR(45)     | IPv4 or IPv6                        |
| created_at | TIMESTAMP       |                                     |

### jwt_blacklist

| Column     | Type            | Notes  |
| ---------- | --------------- | ------ |
| id         | BIGINT UNSIGNED | PK     |
| jti        | VARCHAR(64)     | Unique |
| expires_at | DATETIME        |        |
| created_at | TIMESTAMP       |        |

Index on `expires_at` for cleanup queries.

### migrations

| Column      | Type         | Notes              |
| ----------- | ------------ | ------------------ |
| id          | INT UNSIGNED | PK                 |
| migration   | VARCHAR(255) | Unique, class name |
| batch       | INT UNSIGNED |                    |
| executed_at | TIMESTAMP    |                    |

---

## Default Accounts

`UserSeeder` creates three accounts. Change these before deploying:

| Email                  | Password      | Role          |
| ---------------------- | ------------- | ------------- |
| `admin@galonku.com`    | `admin123`    | administrator |
| `agent@galonku.com`    | `agent123`    | agent         |
| `customer@galonku.com` | `customer123` | customer      |

`ProductSeeder` creates five products:

| SKU           | Name                | Category  | Price     | Stock |
| ------------- | ------------------- | --------- | --------- | ----- |
| GLN-AQUA-19L  | Galon Aqua 19L      | galon     | 20000.00  | 50    |
| GLN-RO-19L    | Galon Isi Ulang 19L | galon     | 6000.00   | 100   |
| GAS-LPG-3KG   | Gas LPG 3kg         | lain      | 25000.00  | 30    |
| GAS-LPG-12KG  | Gas LPG 12kg        | lain      | 180000.00 | 15    |
| AKS-TUTUP-GLN | Tutup Galon         | aksesoris | 5000.00   | 200   |

`CustomerSeeder` creates three customers: Ibu Siti, Pak Budi, and Toko Jaya.

`DemoSeeder` creates one sample transaction (`INV-DEMO-0001`) and one sample schedule. It skips if the demo invoice already exists.

---

## Testing

Three suites:

| Suite       | Location            | Needs DB |
| ----------- | ------------------- | -------- |
| Unit        | `tests/Unit`        | No       |
| Feature     | `tests/Feature`     | Yes      |
| Integration | `tests/Integration` | Yes      |

Prepare the test database once:

```bash
APP_ENV=testing php database/db.php fresh --seed
```

Run:

```bash
composer test              # all suites
composer test:unit         # unit only
composer test:feat         # feature
composer test:int          # integration
composer test:cov          # HTML coverage report to storage/coverage
```

Individual files:

```bash
vendor/bin/phpunit tests/Feature/AuthTest.php
vendor/bin/phpunit --filter test_login_success_returns_tokens
```

### Test Infrastructure

Feature and Integration tests extend `Tests\Support\TestCase`, which:

- Enables `Response::$testMode`. In test mode, `Response::json()` throws a `ResponseCaptured` instead of echoing and calling `exit`. `InteractsWithHttp` catches it and wraps the response in a `TestResponse`.
- Calls `refreshDatabase()` in `setUp()`. On the first test it drops all tables, re-migrates, and re-seeds. Subsequent tests just start a transaction and roll it back in `tearDown()`, so each test gets a clean slate without re-running migrations.
- Rebuilds the router by re-requiring `routes/api.php`.

`ActsAsUser` gives you `loginAsAdmin()`, `loginAsKurir()`, `loginAsPelanggan()`, and `withAuth()`.

`InteractsWithHttp` gives you `get()`, `post()`, `put()`, `patch()`, `delete()`.

`TestResponse` provides assertions: `assertStatus`, `assertOk`, `assertSuccess`, `assertFailed`, `assertUnauthorized`, `assertForbidden`, `assertNotFound`, `assertUnprocessable`, `assertTooManyRequests`, `assertJsonPath`, `assertJsonHas`, `assertJsonMissing`, plus `dump()` for debugging.

`InteractsWithDatabase` provides `assertDatabaseHas()` and `assertDatabaseMissing()`.

Unit tests extend `Tests\Support\UnitTestCase` and never touch the database.

### What's Covered

- Registration, login, refresh, logout, blacklist behavior
- JWT structure, type discrimination, tampering, `jti` uniqueness
- Password hashing and verification
- Request factory and header extraction
- Router matching, path parameters, groups, 404, 405
- Validator rules
- Role-based access control on every protected endpoint
- CRUD for products, customers, schedules
- Stock movements: transaction-driven `out` rows, admin adjustments, listing with filters
- Transaction creation with stock deduction, rollback on insufficient stock, multi-item handling
- Rate limiting per IP and per endpoint
- Audit log creation
- Full sales flow end-to-end
- Login/logout/login cycle

---

## Security Notes

**Password storage.** `password_hash()` with Argon2id preferred, Bcrypt as fallback. `password_hash` is never serialized in responses.

**JWT.** HS256 with a shared secret. The secret must be at least 32 bytes — the boot check in `public/index.php` refuses to start otherwise. Rotating the secret invalidates all existing tokens immediately.

**Token revocation.** Logout blacklists the current access token (and refresh token if provided). Blacklist rows are keyed on `jti`, so tokens are unique. Cleanup is a `DELETE` on `expires_at < NOW()`.

**SQL injection.** All queries use prepared statements. Column names passed to `Model::first()`, `Model::where()`, and `Model::paginate()` are validated against `^[a-zA-Z0-9_]+$`. `paginate()` validates the `orderBy` clause with a strict regex before interpolating it.

**Stock atomicity.** Every stock change goes through `Database::transaction()` with `SELECT ... FOR UPDATE` locks on the product row. Concurrent sales can't oversell.

**Rate limiting.** Login is capped at 5 attempts per minute per IP. State files live in `storage/cache/` — make sure that directory is not publicly accessible.

**Response headers.** Every response includes `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`, `Content-Security-Policy: default-src 'self'; frame-ancestors 'none'`, and `X-XSS-Protection: 1; mode=block`.

**CORS.** Controlled by `CORS_ALLOWED_ORIGINS`. Set specific origins in production. The default `*` is fine for local dev but should not ship.

**Debug mode.** Never leave `APP_DEBUG=true` in production. Level-500 responses include raw exception messages when it's on, which can leak file paths, query fragments, and internal state.

**File permissions.** `storage/` and its subdirectories need to be writable by the PHP user. Everything else should be read-only. `public/` should contain only `index.php`.

**Environment files.** `.env` and `.env.testing` hold credentials. Add them to `.gitignore`. On production, chmod them to `0600`.

**Production seeding.** `UserSeeder` refuses to run with `APP_ENV=production` unless `SEED_ADMIN_PASSWORD` is set. It does not fall back to the default passwords.

---

## Troubleshooting

### `Cannot connect to MySQL: Access denied for user 'root'@'localhost'`

Credentials in `.env` are wrong or the MySQL user doesn't exist. Test manually:

```bash
mysql -u galonku -p -h 127.0.0.1 galonku_db
```

If that fails, recreate the user following the [Installation](#installation) section.

The migrator reads `APP_ENV` from the shell, not from `$_ENV`. If your shell profile exports `APP_ENV`, it affects which `.env` file loads. Confirm with:

```bash
echo $APP_ENV
```

### `Missing .env.testing in /path/to/backend`

The test file doesn't exist. Run `cp .env.testing.example .env.testing` and edit it.

### `JWT_SECRET must be set and at least 32 characters long`

The secret in `.env` is missing or too short. Generate one with `php -r "echo bin2hex(random_bytes(32));"` and paste it into `JWT_SECRET`. Same for `.env.testing`.

### `Server misconfigured` on every request

`public/index.php` bails out before booting if it can't find a valid JWT secret. This usually means Dotenv failed to load `.env` — check the file path and permissions.

### Seeder fails with a foreign key violation

A seeder's priority puts it before a table it depends on. Check `priority()` in each seeder file. `DemoSeeder` in particular needs `UserSeeder`, `ProductSeeder`, and `CustomerSeeder` to have run.

### Rate limit blocks legitimate requests during development

Clear the rate limit cache:

```bash
rm -f storage/cache/rl_*.json
```

Or raise the limit in `.env`:

```
RATE_LIMIT_LOGIN=100
```

### `Class 'Migrator' not found` in tests

The test bootstrap `require_once`s `database/Migrator.php`. If you moved that file, update `tests/Support/Concerns/InteractsWithDatabase.php`.

### Tests fail because tables already contain data

The test `setUp()` calls `refreshDatabase()` which drops and re-creates everything on the first test. If tests still see stale data, confirm your test extends `Tests\Support\TestCase` and not `UnitTestCase`.

### Response body is empty in tests

`Response::$testMode` must be `true`. That's set in `tests/bootstrap.php` and again in `TestCase::setUp()`. If you write a test that bypasses `TestCase`, set it manually:

```php
\App\Core\Response::$testMode = true;
```

### Stock movements aren't being recorded for a transaction

Check that the transaction actually succeeded (look for the `201` response with an `id`). The `StockMovement::create()` calls happen inside `TransactionService::create()`, wrapped in `Database::transaction()`. If anything later in the flow throws, the whole thing rolls back including the movement rows. This is intentional.

### `Insufficient stock` on a transaction you thought would work

The check is `stock < qty` at the moment of the `FOR UPDATE` lock. If another transaction grabbed the lock first and reduced stock below what you need, yours fails. Retry after a moment, or check the stock endpoint for the current value.

---

## License

GPL-3.0-only. See the `LICENSE` file.
