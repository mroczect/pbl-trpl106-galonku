# Galonku Backend API

Backend service for a water depot management system. Handles authentication, product inventory, customer records, transaction processing, delivery scheduling, and audit logging.

Built with **vanilla PHP 8.1+** and **PDO**. No framework. Three Composer dependencies only: `vlucas/phpdotenv`, `firebase/php-jwt`, `monolog/monolog`.

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
  - [Response Format](#response-format)
  - [Error Codes](#error-codes)
  - [Auth Endpoints](#auth-endpoints)
  - [Product Endpoints](#product-endpoints)
  - [Customer Endpoints](#customer-endpoints)
  - [Transaction Endpoints](#transaction-endpoints)
  - [Schedule Endpoints](#schedule-endpoints)
  - [User Endpoints](#user-endpoints)
  - [Role Endpoints](#role-endpoints)
  - [Log Endpoints](#log-endpoints)
  - [Health Check](#health-check)
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

Verify with:

```bash
php -m | grep -E 'pdo|json|mbstring|openssl'
```

---

## Installation

Clone the repository and install dependencies:

```bash
git clone https://github.com/mroczect/pbl-trpl106-galonku
cd pbl-trpl106-galonku/backend/
composer install
```

Copy environment files:

```bash
cp .env.example .env
cp .env.testing.example .env.testing
```

Create database users (log in as MySQL admin first):

```sql
CREATE USER 'galonku'@'127.0.0.1' IDENTIFIED BY 'your_password_here';
CREATE USER 'galonku'@'localhost' IDENTIFIED BY 'your_password_here';

GRANT ALL PRIVILEGES ON galonku_db.*   TO 'galonku'@'127.0.0.1';
GRANT ALL PRIVILEGES ON galonku_db.*   TO 'galonku'@'localhost';
GRANT ALL PRIVILEGES ON galonku_test.* TO 'galonku'@'127.0.0.1';
GRANT ALL PRIVILEGES ON galonku_test.* TO 'galonku'@'localhost';

FLUSH PRIVILEGES;
```

Generate a JWT secret:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Paste the output into both `.env` and `.env.testing`. They may be identical or different; it does not matter for correctness, only for test isolation.

Build the schema and seed initial data:

```bash
php database/db.php fresh --seed
```

Start the development server:

```bash
composer serve
```

The API is available at `http://localhost:8000`.

---

## Configuration

All configuration is loaded from `.env`. The file is parsed manually by `public/index.php` and `database/db.php`.

### Application

| Variable       | Default        | Description                                                       |
| -------------- | -------------- | ----------------------------------------------------------------- |
| `APP_NAME`     | `Galonku API`  | Application name, returned by the health check endpoint           |
| `APP_ENV`      | `production`   | Environment: `local`, `testing`, `production`                     |
| `APP_DEBUG`    | `false`        | When `true`, error responses include stack traces and DB messages |
| `APP_TIMEZONE` | `Asia/Jakarta` | PHP timezone setting                                              |

### Database

| Variable      | Default      | Description        |
| ------------- | ------------ | ------------------ |
| `DB_HOST`     | `127.0.0.1`  | MySQL host         |
| `DB_PORT`     | `3306`       | MySQL port         |
| `DB_DATABASE` | `galonku_db` | Database name      |
| `DB_USERNAME` | `root`       | Database user      |
| `DB_PASSWORD` | _(empty)_    | Database password  |
| `DB_CHARSET`  | `utf8mb4`    | Connection charset |

### JWT

| Variable             | Default   | Description                                        |
| -------------------- | --------- | -------------------------------------------------- |
| `JWT_SECRET`         | –         | HMAC secret. Minimum 32 bytes. Use a random value. |
| `JWT_ACCESS_EXPIRE`  | `3600`    | Access token lifetime in seconds                   |
| `JWT_REFRESH_EXPIRE` | `2592000` | Refresh token lifetime in seconds (30 days)        |

### CORS and Rate Limiting

| Variable               | Default | Description                              |
| ---------------------- | ------- | ---------------------------------------- |
| `CORS_ALLOWED_ORIGINS` | `*`     | Allowed origins, comma-separated, or `*` |
| `RATE_LIMIT_LOGIN`     | `5`     | Max login attempts per window per IP     |
| `RATE_LIMIT_WINDOW`    | `60`    | Rate limit window in seconds             |

### Testing Environment

`.env.testing` is loaded when `APP_ENV=testing`. Point it at a separate database (`galonku_test`) using the same credentials. Both `.env` and `.env.testing` must define `JWT_SECRET` and database credentials.

---

## Database CLI

All schema and data operations go through `php database/db.php`. The script reads `APP_ENV` from the shell to determine which `.env` file to load.

```
Usage: php database/db.php <command> [options]
```

| Command          | Description                                            |
| ---------------- | ------------------------------------------------------ |
| `migrate`        | Run all pending migrations                             |
| `rollback [n]`   | Roll back the last `n` batches. Defaults to `1`.       |
| `fresh [--seed]` | Drop all tables, re-migrate, optionally seed           |
| `reset [--seed]` | Roll back all, re-migrate, optionally seed             |
| `seed [name]`    | Run all seeders, or a single seeder by class name      |
| `status`         | Show migration and seeder status                       |
| `drop --force`   | Drop the entire database. Requires the `--force` flag. |
| `help`           | Print help text                                        |

Examples:

```bash
php database/db.php migrate
php database/db.php fresh --seed
php database/db.php seed RoleSeeder
php database/db.php status
APP_ENV=testing php database/db.php fresh --seed
```

Composer shortcuts for common commands:

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

Migration files live in `database/migrations/`. Each file returns an anonymous class extending `Migration` and implementing two methods:

```php
return new class extends Migration {
    public function up(PDO $pdo): void { /* create tables */ }
    public function down(PDO $pdo): void { /* drop tables */ }
};
```

Filename format: `<timestamp>_<description>.php`. Files whose names begin with an underscore are treated as base classes and skipped by the migrator.

`up()` is **idempotent**: the base class checks `SHOW TABLES LIKE` before `CREATE TABLE`. Running `migrate` repeatedly will not fail because a table already exists.

MySQL and MariaDB auto-commit DDL statements, so migrations do not run inside a transaction. If a migration fails midway, the migrator reports the error and halts; the operator must inspect state manually.

### Seeders

Seeders live in `database/seeders/`. Each file returns an anonymous class extending `Seeder` with a `run(PDO $pdo)` method.

The migrator runs seeders in a fixed order, **not alphabetical**:

1. `RoleSeeder`
2. `UserSeeder`
3. `ProductSeeder`
4. `CustomerSeeder`
5. `DemoSeeder`

This order respects foreign key dependencies. Seeders use `ON DUPLICATE KEY UPDATE` so they are safe to run multiple times.

To add a new seeder: create the file in `database/seeders/` and append its class name to the `SEEDER_ORDER` constant in `database/Migrator.php`.

---

## Running the Server

Development:

```bash
composer serve
```

This invokes `php -S localhost:8000 -t public`. The router directs every request to `public/index.php`.

For production, point your web server (Nginx, Apache, Caddy) at the `public/` directory. All requests must be routed to `public/index.php` except static files.

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

The `public/` directory contains only `index.php`. No other file is exposed.

---

## Project Structure

```
backend/
├── app/
│   ├── Controllers/Api/V1/       HTTP controllers, one per resource
│   ├── Core/                     Framework primitives
│   │   ├── App.php               Bootstrap: error handler, CORS, router
│   │   ├── Auth.php              Authentication state and helpers
│   │   ├── Database.php          PDO connection singleton
│   │   ├── Model.php             Base model with query helpers
│   │   ├── Request.php           Request wrapper
│   │   ├── Response.php          JSON response helper
│   │   ├── Router.php            Route registry and dispatcher
│   │   └── Validator.php         Input validation
│   ├── Exceptions/               ValidationException, AuthException, NotFoundException, ErrorHandler
│   ├── Middleware/               AuthMiddleware, RoleMiddleware, RateLimitMiddleware
│   ├── Models/                   User, Product, Customer, Transaction, Schedule, Role, Log
│   ├── Services/                 AuthService, TransactionService, StockService
│   ├── Support/                  Jwt, AppLogger, helpers.php
│   └── Testing/                  Exception ResponseCaptured for tests
├── config/
│   ├── app.php
│   ├── cors.php
│   └── database.php
├── database/
│   ├── Migrator.php              Migration and seeder engine
│   ├── db.php                    CLI entry point
│   ├── migrations/               One file per table
│   └── seeders/                  One file per seed concern
├── public/
│   └── index.php                 Single entry point
├── routes/
│   └── api.php                   Route definitions
├── storage/
│   ├── cache/                    Rate limit JSON files
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

---

## Architecture

### Request Lifecycle

1. The web server forwards the request to `public/index.php`.
2. Dotenv loads environment variables from `.env` (or `.env.testing` if applicable).
3. `App::boot()` registers the error handler, CORS headers, and security headers. It then creates the router and loads route definitions from `routes/api.php`.
4. `App::run()` captures the current request via `Request::capture()` and dispatches it through `Router::dispatch()`.
5. The router matches the path and method against registered routes. On a match, it runs middleware in sequence, then invokes the controller action.
6. The controller calls services and models, then emits a response via `Response::success()` or `Response::error()`. Both call `exit` after emitting JSON.
7. Uncaught exceptions reach `ErrorHandler::handle()`, which maps known exception types to HTTP status codes and logs unknown ones to `storage/logs/app.log`.

### Routing

Routes are registered with `$router->get()`, `->post()`, `->put()`, `->patch()`, and `->delete()`. Path parameters use the `{name}` syntax and are passed to the controller as positional arguments after the `Request` object.

```php
$router->get('/products/{id}', [ProductController::class, 'show']);
// ProductController::show(Request $req, int $id)
```

Route groups apply a shared prefix and middleware list:

```php
$router->group(['prefix' => '/api/v1', 'middleware' => [AuthMiddleware::class]], function ($router) {
    // ...
});
```

Middleware is referenced by class name, optionally with arguments after a colon:

```php
[RoleMiddleware::class . ':admin']
[RateLimitMiddleware::class . ':login:5:60']
```

### Response Contract

Every response is JSON with the following shape.

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

The `meta` field appears only on paginated endpoints. The `errors` field appears only on validation failures and typically maps field names to arrays of messages.

### Error Handling

`ErrorHandler::handle()` maps exceptions to HTTP status codes:

| Exception                  | Status |
| -------------------------- | ------ |
| `ValidationException`      | 422    |
| `AuthException`            | 401    |
| `NotFoundException`        | 404    |
| `InvalidArgumentException` | 400    |
| Any other exception        | 500    |

Unhandled exceptions are logged to `storage/logs/app.log` via Monolog with file, line, and stack trace. When `APP_DEBUG=true`, the exception message is included in the response. In production, the response is a generic `Internal server error`.

### Validation

Validation rules are declared per endpoint as a `field => rules` map. Rule strings use `|` as a separator and `:` for parameters:

```php
$data = $req->validate([
    'name'     => 'required|min:3|max:100',
    'email'    => 'required|email|max:150',
    'password' => 'required|min:6|max:100',
    'phone'    => 'phone',
]);
```

Supported rules: `required`, `email`, `min`, `max`, `numeric`, `integer`, `in`, `array`, `date`, `boolean`, `phone`.

`Validator::make()` returns only the fields that pass validation. Fields that are absent or null and not `required` are omitted from the result. If any rule fails, a `ValidationException` is thrown carrying all per-field errors.

---

## Authentication

The API uses JWT tokens with HS256 signatures.

### Token Types

Two tokens are issued at login:

- **Access token** – Short-lived (default 1 hour). Sent in the `Authorization` header on every protected request. Contains `sub` (user ID), `email`, `role`, `typ=access`, `jti`, `iat`, `exp`.
- **Refresh token** – Long-lived (default 30 days). Used only at `POST /api/v1/auth/refresh`. Contains `sub`, `typ=refresh`, `jti`, `iat`, `exp`. No role or email.

Both tokens carry a `jti` claim (32-character hex string). The `jti` is the identifier used for blacklisting.

### Protected Requests

```
Authorization: Bearer <access_token>
```

`AuthMiddleware` reads the token, verifies the signature, ensures `typ=access`, checks the `jti` against the blacklist, and loads the user from the database. If any step fails, the response is `401 Unauthorized`.

### Refresh Flow

```
POST /api/v1/auth/refresh
{ "refresh_token": "<token>" }
```

The endpoint verifies the refresh token, confirms the user exists and is active, then issues a new token pair. The old refresh token is not invalidated. The client rotates tokens by overwriting its stored pair.

### Logout

```
POST /api/v1/auth/logout
Authorization: Bearer <access_token>
```

The endpoint inserts the current access token's `jti` into `jwt_blacklist` along with its expiry. Subsequent requests using that token return 401. Refresh tokens are unaffected. Blacklist cleanup is performed in `DemoSeeder` and can be scheduled as a cron job:

```sql
DELETE FROM jwt_blacklist WHERE expires_at < NOW();
```

### Password Hashing

Passwords are hashed with Argon2id when available, falling back to Bcrypt. `Auth::hash()` selects the algorithm at runtime. Hashes are never returned in API responses; controllers strip `password_hash` before serializing user records.

---

## API Reference

Base URL: `/api/v1`. All requests and responses use `Content-Type: application/json`.

### Response Format

Paginated list endpoints accept `page` and `per_page` query parameters. `per_page` is capped at 100. The response includes:

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

| Status | Meaning                                           |
| ------ | ------------------------------------------------- |
| 200    | Success                                           |
| 201    | Resource created                                  |
| 400    | Bad request, invalid argument                     |
| 401    | Missing or invalid credentials                    |
| 403    | Authenticated but not authorized                  |
| 404    | Resource not found                                |
| 405    | Method not allowed for this path                  |
| 409    | Conflict, typically a unique constraint violation |
| 422    | Validation failed                                 |
| 429    | Rate limit exceeded                               |
| 500    | Unhandled server error                            |

---

### Auth Endpoints

#### Register

```
POST /api/v1/auth/register
```

Public. Creates a user with the `pelanggan` role.

Request body:

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
{
  "success": true,
  "message": "Registration successful",
  "data": { "id": 4 }
}
```

Returns `409` if the email already exists. Returns `422` on validation failure.

---

#### Login

```
POST /api/v1/auth/login
```

Public. Rate-limited at 5 requests per 60 seconds per IP. A successful login updates `users.last_login_at` and writes an entry to `logs`.

Request body:

| Field      | Type   | Required |
| ---------- | ------ | -------- |
| `email`    | string | yes      |
| `password` | string | yes      |

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
      "name": "Admin Galonku",
      "email": "admin@galonku.com",
      "phone": "081234567890",
      "role_id": 1,
      "role_name": "admin"
    },
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

Returns `401` if credentials are wrong or the account is inactive. Returns `429` if the rate limit is exceeded.

---

#### Refresh Token

```
POST /api/v1/auth/refresh
```

Public. Exchanges a valid refresh token for a new token pair.

Request body:

| Field           | Type   | Required |
| --------------- | ------ | -------- |
| `refresh_token` | string | yes      |

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

Returns `401` if the token is malformed, expired, or the wrong type. Access tokens are rejected at this endpoint.

---

#### Current User

```
GET /api/v1/auth/me
Authorization: Bearer <token>
```

Returns the authenticated user's record. `password_hash` is stripped.

Response `200`:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 1,
    "role_id": 1,
    "name": "Admin Galonku",
    "email": "admin@galonku.com",
    "phone": "081234567890",
    "is_active": 1,
    "last_login_at": "2026-09-17 04:21:33",
    "created_at": "2026-09-17 04:00:00",
    "updated_at": "2026-09-17 04:21:33",
    "role_name": "admin"
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

Blacklists the current access token. Writes a `logout` action to `logs`.

Response `200`:

```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null
}
```

---

### Product Endpoints

Products have an SKU, name, category, price, stock, and active flag. Categories: `galon`, `air`, `aksesoris`, `lain`.

#### List Products

```
GET /api/v1/products?page=1&per_page=15&category=galon
Authorization: Bearer <token>
```

Any authenticated user. Returns a paginated list. `category` is optional; when provided, only products in that category are returned.

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
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 5,
    "total_pages": 1
  }
}
```

---

#### Get Product

```
GET /api/v1/products/{id}
Authorization: Bearer <token>
```

Any authenticated user. Returns `404` if the product does not exist.

---

#### Low-Stock Products

```
GET /api/v1/products/low-stock?threshold=10
Authorization: Bearer <token>
```

Any authenticated user. Returns active products with `stock <= threshold`. The default threshold is 10.

Response `200`:

```json
{
  "success": true,
  "message": "OK",
  "data": []
}
```

---

#### Create Product

```
POST /api/v1/products
Authorization: Bearer <token>
Role: admin
```

Request body:

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

Response `201`:

```json
{
  "success": true,
  "message": "Product created",
  "data": { "id": 6 }
}
```

Returns `409` if the SKU already exists, `422` on validation failure, `403` for non-admins.

---

#### Update Product

```
PUT /api/v1/products/{id}
Authorization: Bearer <token>
Role: admin
```

All fields optional. Only the fields sent are updated. Accepts `name`, `category`, `price`, `stock`, `is_active`.

Response `200`:

```json
{
  "success": true,
  "message": "Product updated",
  "data": null
}
```

Returns `404` if the product does not exist. Returns `400` if no fields are provided.

---

#### Delete Product

```
DELETE /api/v1/products/{id}
Authorization: Bearer <token>
Role: admin
```

Soft delete: sets `is_active = 0`. The row remains in the database. Transactions and transaction items referencing this product still resolve.

Response `200`:

```json
{
  "success": true,
  "message": "Product deactivated",
  "data": null
}
```

---

### Customer Endpoints

#### List Customers

```
GET /api/v1/customers?page=1&per_page=15
Authorization: Bearer <token>
```

Any authenticated user. Returns a paginated list ordered by ID descending.

---

#### Get Customer

```
GET /api/v1/customers/{id}
Authorization: Bearer <token>
```

Any authenticated user. Returns `404` if not found.

---

#### Create Customer

```
POST /api/v1/customers
Authorization: Bearer <token>
```

Any authenticated user.

Request body:

| Field     | Type   | Required | Rules                                    |
| --------- | ------ | -------- | ---------------------------------------- |
| `name`    | string | yes      | 3–100 characters                         |
| `phone`   | string | yes      | 8–20 digits, `+`, `-`, or spaces, unique |
| `address` | string | no       | Free text                                |
| `notes`   | string | no       | Free text                                |

Response `201`:

```json
{
  "success": true,
  "message": "Customer created",
  "data": { "id": 4 }
}
```

Returns `409` if the phone number already exists.

---

#### Update Customer

```
PUT /api/v1/customers/{id}
Authorization: Bearer <token>
```

Accepts `name`, `phone`, `address`, `notes`, `is_active`.

Response `200` with `{ "success": true, "message": "Customer updated", "data": null }`.

---

#### Delete Customer

```
DELETE /api/v1/customers/{id}
Authorization: Bearer <token>
Role: admin
```

Soft delete: sets `is_active = 0`. Admins only.

---

### Transaction Endpoints

Transactions have a header (`transactions`) and one or more items (`transaction_items`). Creating a transaction deducts product stock atomically within a database transaction. If any item fails (insufficient stock, unknown product), the entire transaction is rolled back.

#### List Transactions

```
GET /api/v1/transactions?page=1&per_page=15&status=paid&customer_id=1&user_id=1&type=sale
Authorization: Bearer <token>
```

Any authenticated user. All filters optional. Returns headers with joined customer and user names.

Response `200`:

```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
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
      "user_name": "Admin Galonku"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 1,
    "total_pages": 1
  }
}
```

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
    "user_name": "Admin Galonku",
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

Any authenticated user. Runs inside a single database transaction with `SELECT ... FOR UPDATE` locks on each product row.

Request body:

| Field         | Type    | Required | Rules                                                         |
| ------------- | ------- | -------- | ------------------------------------------------------------- |
| `customer_id` | integer | yes      | Must exist                                                    |
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

The server will:

1. Generate an invoice number in the format `INV-YYYYMMDD-XXXX`.
2. Iterate over items, lock each product row, and validate stock.
3. Compute subtotals and total.
4. Insert the transaction header.
5. Insert each item and decrement product stock.
6. Write an entry to `logs`.
7. Commit.

If any step fails, the transaction is rolled back. Stock is not deducted and no header is created.

Response `201`:

```json
{
  "success": true,
  "message": "Transaction created successfully",
  "data": {
    "id": 1,
    "invoice_no": "INV-20260917-8C4A",
    "total_amount": 46000,
    "items_count": 2
  }
}
```

Errors:

- `422` if `customer_id` or `items` are missing or invalid.
- `404` if a referenced product does not exist.
- `500` if stock is insufficient. Message is `Insufficient stock for <name>` when `APP_DEBUG=true`.

---

#### Update Transaction Status

```
PUT /api/v1/transactions/{id}/status
Authorization: Bearer <token>
```

Any authenticated user.

Request body:

| Field         | Type    | Required | Rules                                        |
| ------------- | ------- | -------- | -------------------------------------------- |
| `status`      | string  | yes      | `pending`, `paid`, `partial`, or `cancelled` |
| `paid_amount` | numeric | no       | –                                            |

Response `200`:

```json
{
  "success": true,
  "message": "Transaction status updated",
  "data": null
}
```

---

### Schedule Endpoints

Schedules assign a customer to a courier (user) at a specific future time.

#### List Schedules

```
GET /api/v1/schedules?page=1&per_page=15&status=pending&user_id=2&customer_id=1
Authorization: Bearer <token>
```

Any authenticated user. All filters optional. Ordered by `scheduled_at` descending. The response includes joined `customer_name` and `user_name`.

---

#### Get Schedule

```
GET /api/v1/schedules/{id}
Authorization: Bearer <token>
```

Any authenticated user. Returns `404` if not found.

---

#### Create Schedule

```
POST /api/v1/schedules
Authorization: Bearer <token>
```

Request body:

| Field          | Type    | Required       |
| -------------- | ------- | -------------- |
| `customer_id`  | integer | yes            |
| `user_id`      | integer | yes            |
| `scheduled_at` | string  | yes (datetime) |
| `notes`        | string  | no             |

New schedules default to `pending`.

Response `201`:

```json
{
  "success": true,
  "message": "Schedule created",
  "data": { "id": 1 }
}
```

---

#### Update Schedule Status

```
PUT /api/v1/schedules/{id}/status
Authorization: Bearer <token>
```

Request body:

| Field    | Type   | Required | Rules                                         |
| -------- | ------ | -------- | --------------------------------------------- |
| `status` | string | yes      | `pending`, `on_route`, `done`, or `cancelled` |

Response `200`:

```json
{
  "success": true,
  "message": "Schedule status updated",
  "data": null
}
```

---

### User Endpoints

All endpoints in this section are admin-only.

#### List Users

```
GET /api/v1/users?page=1&per_page=15
Authorization: Bearer <token>
Role: admin
```

Returns a paginated list with joined role names.

---

#### Get User

```
GET /api/v1/users/{id}
Authorization: Bearer <token>
Role: admin
```

Returns the user record with `password_hash` stripped.

---

#### Update User

```
PUT /api/v1/users/{id}
Authorization: Bearer <token>
Role: admin
```

Accepts `name`, `phone`, `role_id`, `is_active`.

Response `200` with `{ "success": true, "message": "User updated", "data": null }`.

---

#### Delete User

```
DELETE /api/v1/users/{id}
Authorization: Bearer <token>
Role: admin
```

Soft delete: sets `is_active = 0`.

---

### Role Endpoints

```
GET /api/v1/roles
Authorization: Bearer <token>
Role: admin
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
      "name": "admin",
      "description": "System administrator",
      "created_at": "...",
      "updated_at": "..."
    },
    {
      "id": 2,
      "name": "kurir",
      "description": "Delivery courier",
      "created_at": "...",
      "updated_at": "..."
    },
    {
      "id": 3,
      "name": "pelanggan",
      "description": "Water depot customer",
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
Role: admin
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
      "user_name": "Admin Galonku"
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

Returns application status. No authentication required.

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

Rate limiting is applied per endpoint via `RateLimitMiddleware`, declared in the route definition:

```php
$router->post('/auth/login', [AuthController::class, 'login'],
    [RateLimitMiddleware::class . ':login:5:60']);
```

Three arguments follow the class name: identifier prefix, maximum requests, and window in seconds. The bucket key is `md5(ip:prefix)`. State is stored in `storage/cache/rl_<hash>.json`.

When the limit is exceeded, the server returns `429` with a `Retry-After` header indicating the number of seconds to wait.

The cache directory must be writable by the web server user. Add a cron job to periodically clean stale files:

```bash
find storage/cache -name 'rl_*.json' -mmin +60 -delete
```

---

## Database Schema

Ten tables, all InnoDB, all `utf8mb4_unicode_ci`.

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

### transaction_items

| Column         | Type          | Notes                                     |
| -------------- | ------------- | ----------------------------------------- |
| id             | INT UNSIGNED  | PK                                        |
| transaction_id | INT UNSIGNED  | FK → transactions.id, `ON DELETE CASCADE` |
| product_id     | INT UNSIGNED  | FK → products.id                          |
| qty            | INT           | CHECK `qty > 0`                           |
| unit_price     | DECIMAL(12,2) |                                           |
| subtotal       | DECIMAL(14,2) |                                           |

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

### migrations

| Column      | Type         | Notes              |
| ----------- | ------------ | ------------------ |
| id          | INT UNSIGNED | PK                 |
| migration   | VARCHAR(255) | Unique, class name |
| batch       | INT UNSIGNED |                    |
| executed_at | TIMESTAMP    |                    |

---

## Default Accounts

`UserSeeder` creates three accounts. Change these passwords before deploying to production.

| Email               | Password       | Role      |
| ------------------- | -------------- | --------- |
| `admin@galonku.com` | `admin123`     | admin     |
| `kurir@galonku.com` | `kurir123`     | kurir     |
| `user@galonku.com`  | `pelanggan123` | pelanggan |

`ProductSeeder` creates five sample products. `CustomerSeeder` creates three sample customers. `DemoSeeder` creates one sample transaction and one sample schedule, and is idempotent (skips if `transactions` is already populated).

---

## Testing

Tests are divided into three suites.

| Suite       | Location            | Needs DB |
| ----------- | ------------------- | -------- |
| Unit        | `tests/Unit`        | No       |
| Feature     | `tests/Feature`     | Yes      |
| Integration | `tests/Integration` | Yes      |

Prepare the test database once:

```bash
APP_ENV=testing php database/db.php fresh --seed
```

Then run:

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

- Enables `Response::$testMode`. In this mode, `Response::json()` throws a `ResponseCaptured` instead of printing and calling `exit`. The HTTP interaction trait catches this and returns a `TestResponse` object.
- Calls `refreshDatabase()` in `setUp()`. This drops all tables, re-runs all migrations, re-runs all seeders, and clears rate limit cache files.
- Boots a fresh router by re-requiring `routes/api.php`.

`ActsAsUser` provides `loginAsAdmin()`, `loginAsKurir()`, `loginAsPelanggan()`, and `withAuth()` to build authenticated headers.

`InteractsWithHttp` provides `get()`, `post()`, `put()`, `patch()`, and `delete()` helpers.

`TestResponse` provides assertion helpers: `assertStatus`, `assertOk`, `assertSuccess`, `assertFailed`, `assertUnauthorized`, `assertForbidden`, `assertNotFound`, `assertUnprocessable`, `assertTooManyRequests`, `assertJsonPath`, `assertJsonHas`, and `assertJsonMissing`.

`InteractsWithDatabase` provides `assertDatabaseHas()` and `assertDatabaseMissing()`.

Unit tests extend `Tests\Support\UnitTestCase`, which does not touch the database.

### Test Coverage

Current tests cover:

- Registration, login, refresh, logout, and blacklist behavior
- JWT structure, type discrimination, tampering, and `jti` uniqueness
- Password hashing and verification
- Request factory and header extraction
- Router matching, path parameters, groups, 404, and 405
- Validation rules
- Role-based access control on every protected endpoint
- CRUD for products, customers, and schedules
- Transaction creation with stock deduction, rollback on insufficient stock, and multi-item handling
- Rate limiting
- Audit log creation
- Full sales flow end-to-end
- Login/logout/login cycle

---

## Security Notes

**Password.** Stored with `password_hash()`. Argon2id is preferred; Bcrypt is the fallback. The `password_hash` column is never returned in API responses.

**JWT.** HS256 with a shared secret. The secret must be at least 32 random bytes. Rotate by updating `.env` and restarting; existing tokens become invalid immediately.

**Token revocation.** Logout inserts the current access token's `jti` into `jwt_blacklist`. Refresh tokens are not blacklisted. If stricter session control is required, blacklist refresh token `jti` as well on logout.

**SQL injection.** All queries use prepared statements. Column names passed to `Model::first()` and `Model::where()` are validated against `^[a-zA-Z0-9_]+$`. `paginate()` validates filter keys before interpolating them into the `WHERE` clause.

**Rate limiting.** Login is limited to 5 attempts per minute per IP. Rate limit state files live on disk; ensure the cache directory is not publicly accessible.

**Headers.** Every response includes `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`, and `X-XSS-Protection: 1; mode=block`.

**CORS.** Controlled by `CORS_ALLOWED_ORIGINS`. Set this to specific origins in production. The `*` default is only suitable for development.

**Debug mode.** Never leave `APP_DEBUG=true` in production. When enabled, level 500 responses include raw exception messages that may leak file paths, query fragments, or internal state.

**File permissions.** `storage/` and its subdirectories must be writable by the web server process. Everything else should be read-only. The `public/` directory must contain only `index.php`.

**Environment files.** `.env` and `.env.testing` contain credentials. Ensure they are in `.gitignore` and never committed. Restrict file permissions to `0600` on production systems.

---

## Troubleshooting

### `Cannot connect to MySQL: Access denied for user 'root'@'localhost'`

Database credentials in `.env` are wrong, or the MySQL user does not exist. Verify with a manual login:

```bash
mysql -u galonku -p -h 127.0.0.1 galonku_db
```

If this fails, recreate the user as shown in [Installation](#installation).

The migrator reads `APP_ENV` from the shell, not from `$_ENV`. If `APP_ENV` is set in your shell profile, it affects which `.env` file is loaded. Confirm with:

```bash
echo $APP_ENV
```

### `Missing .env.testing in /path/to/backend`

`.env.testing` has not been created. Run `cp .env.testing.example .env.testing` and edit it.

### Seeder fails with a foreign key violation

The seeder order was changed, or `DemoSeeder` ran without `UserSeeder`. Restore the seeder order constant in `database/Migrator.php` and re-run:

```bash
php database/db.php fresh --seed
```

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

The test suite requires `database/Migrator.php` at runtime via `require_once`. If you move or rename the file, update `tests/Support/Concerns/InteractsWithDatabase.php`.

### Tests fail because tables already contain data

The test `setUp()` calls `refreshDatabase()`, which drops and recreates everything. If tests still see stale data, verify that the test extends `Tests\Support\TestCase`, not `UnitTestCase`.

### Response body is empty in tests

Ensure `Response::$testMode` is `true`. This is set in `tests/bootstrap.php` and in `TestCase::setUp()`. If you write a test that bypasses the TestCase framework, set it manually:

```php
\App\Core\Response::$testMode = true;
```

---

## License

GPL-3.0-only. See the `LICENSE` file.
