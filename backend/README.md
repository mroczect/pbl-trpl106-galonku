# Galonku Backend API

Backend API for a drinking water depot management application.

## Requirements

- PHP >= 8.1
- Composer
- MariaDB >= 10.5 or MySQL >= 8.0

## Setup

```bash
composer install
cp .env.example .env
cp .env.testing.example .env.testing
```

Edit `.env` and `.env.testing`. Minimum:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=galonku_db
DB_USERNAME=galonku
DB_PASSWORD=galonku_2026

JWT_SECRET=<at least 32 characters>
```

Generate a secret:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Create the database user:

```sql
CREATE USER IF NOT EXISTS 'galonku'@'127.0.0.1' IDENTIFIED BY 'galonku_2026';
CREATE USER IF NOT EXISTS 'galonku'@'localhost' IDENTIFIED BY 'galonku_2026';
GRANT ALL PRIVILEGES ON galonku_db.*   TO 'galonku'@'127.0.0.1', 'galonku'@'localhost';
GRANT ALL PRIVILEGES ON galonku_test.* TO 'galonku'@'127.0.0.1', 'galonku'@'localhost';
FLUSH PRIVILEGES;
```

Migrate and seed:

```bash
php database/db.php fresh --seed
```

Run the server:

```bash
composer serve
```

API is at `http://localhost:8000`.

## Database CLI

```bash
php database/db.php <command> [options]
```

| Command | Description |
|---|---|
| `migrate` | Run pending migrations |
| `rollback [n]` | Roll back the last n batches (default 1) |
| `fresh [--seed]` | Drop all tables, re-migrate, optionally seed |
| `reset [--seed]` | Roll back all, re-migrate, optionally seed |
| `seed [name]` | Run all seeders, or a specific one |
| `status` | Show migration and seeder status |
| `drop --force` | Drop the database |

For the testing database:

```bash
APP_ENV=testing php database/db.php fresh --seed
```

Composer shortcuts:

```bash
composer db:status
composer db:fresh
composer db:seed
composer db:test:fresh
```

## Testing

```bash
composer test        # all
composer test:unit   # unit only, no DB
composer test:feat   # feature, needs DB
composer test:int    # integration, needs DB
```

Prepare the testing database before running feature and integration tests:

```bash
APP_ENV=testing php database/db.php fresh --seed
```

## Endpoints

Base URL: `/api/v1`

### Auth

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/auth/register` | – | Register a new user |
| POST | `/auth/login` | – | Login, rate limited to 5 requests per minute |
| POST | `/auth/refresh` | – | Exchange refresh token for new tokens |
| GET | `/auth/me` | Bearer | Current user |
| POST | `/auth/logout` | Bearer | Blacklist the access token |

### Products

| Method | Path | Auth | Role |
|---|---|---|---|
| GET | `/products` | Bearer | any |
| GET | `/products/low-stock` | Bearer | any |
| GET | `/products/{id}` | Bearer | any |
| POST | `/products` | Bearer | admin |
| PUT | `/products/{id}` | Bearer | admin |
| DELETE | `/products/{id}` | Bearer | admin |

Query params: `page`, `per_page`, `category`.

### Customers

| Method | Path | Auth | Role |
|---|---|---|---|
| GET | `/customers` | Bearer | any |
| GET | `/customers/{id}` | Bearer | any |
| POST | `/customers` | Bearer | any |
| PUT | `/customers/{id}` | Bearer | any |
| DELETE | `/customers/{id}` | Bearer | admin |

### Transactions

| Method | Path | Auth | Role |
|---|---|---|---|
| GET | `/transactions` | Bearer | any |
| GET | `/transactions/{id}` | Bearer | any |
| POST | `/transactions` | Bearer | any |
| PUT | `/transactions/{id}/status` | Bearer | any |

Query params: `page`, `per_page`, `status`, `customer_id`, `user_id`, `type`.

### Schedules

| Method | Path | Auth | Role |
|---|---|---|---|
| GET | `/schedules` | Bearer | any |
| GET | `/schedules/{id}` | Bearer | any |
| POST | `/schedules` | Bearer | any |
| PUT | `/schedules/{id}/status` | Bearer | any |

### Users, Roles, Logs

| Method | Path | Auth | Role |
|---|---|---|---|
| GET | `/users` | Bearer | admin |
| GET | `/users/{id}` | Bearer | admin |
| PUT | `/users/{id}` | Bearer | admin |
| DELETE | `/users/{id}` | Bearer | admin |
| GET | `/roles` | Bearer | admin |
| GET | `/logs` | Bearer | admin |

### Health

| Method | Path | Description |
|---|---|---|
| GET | `/` | Application status |

## Response Format

Success:

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 42,
    "total_pages": 3
  }
}
```

`meta` only appears on paginated endpoints.

Error:

```json
{
  "success": false,
  "message": "Validasi gagal",
  "errors": {
    "email": ["email wajib diisi"]
  }
}
```

HTTP status: 200, 201, 400, 401, 403, 404, 405, 409, 422, 429, 500.

## Usage

### Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@galonku.com", "password": "admin123"}'
```

### Authenticated request

```bash
curl http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer <access_token>"
```

### Create a transaction

```bash
curl -X POST http://localhost:8000/api/v1/transactions \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1,
    "type": "sale",
    "items": [
      {"product_id": 1, "qty": 2},
      {"product_id": 2, "qty": 1}
    ]
  }'
```

## Default Seeder Accounts

| Email | Password | Role |
|---|---|---|
| admin@galonku.com | admin123 | admin |
| kurir@galonku.com | kurir123 | kurir |
| user@galonku.com | pelanggan123 | pelanggan |

Change these passwords before deploying to production.

## Database Schema

| Table | Description |
|---|---|
| `roles` | Role definitions |
| `users` | User accounts, FK to roles |
| `customers` | Customer records |
| `products` | Product catalog |
| `transactions` | Transaction headers |
| `transaction_items` | Line items per transaction |
| `schedules` | Delivery schedules |
| `logs` | Audit log |
| `jwt_blacklist` | Revoked tokens |

All tables use InnoDB with utf8mb4.

## Notes

- Passwords hashed with Argon2id, fallback to Bcrypt.
- JWT HS256, secret must be at least 32 bytes.
- Rate limit on login endpoint.
- CORS configured in `config/cors.php`.
- Security headers set in `App::registerSecurityHeaders()`.

## License

GPL-3.0-only. See `LICENSE`.