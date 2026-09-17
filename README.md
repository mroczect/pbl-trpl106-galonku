# PBL-TRPL106-Galonku

A web-based management platform for refillable drinking water depots. Handles order taking, inventory tracking, transaction processing, and delivery scheduling. Consumption-based reorder reminders are planned but not yet implemented.

Project-Based Learning (PBL) deliverable for the Software Engineering Technology (TRPL) program, Politeknik Negeri Batam, cohort 106.

[![License: GPL-3.0](https://img.shields.io/badge/License-GPL%203.0-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)](https://react.dev/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Vite](https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white)](https://vitejs.dev/)

## Table of Contents

- [Overview](#overview)
- [Background](#background)
- [Feature Status](#feature-status)
- [System Architecture](#system-architecture)
- [Tech Stack](#tech-stack)
- [Repository Structure](#repository-structure)
- [Getting Started](#getting-started)
- [User Roles](#user-roles)
- [API Overview](#api-overview)
- [Database Schema](#database-schema)
- [Development Workflow](#development-workflow)
- [Testing](#testing)
- [Deployment and Provisioning](#deployment-and-provisioning)
- [Known Gaps and Roadmap](#known-gaps-and-roadmap)
- [Security Notes](#security-notes)
- [Documentation](#documentation)
- [Team](#team)
- [Contributing](#contributing)
- [License](#license)

## Overview

Galonku is a full-stack web application that provides a digital operating system for small and medium-sized drinking water depots in Indonesia. A depot refills and resells 19-liter gallon jugs of drinking water to households and small businesses. Most depots still operate on paper notebooks, phone calls, and memory.

This project replaces that with a structured platform:

- **Ordering** - take and process refill orders end-to-end.
- **Inventory** - automatic stock deduction per transaction and low-stock warnings.
- **Customer records** - contact details, order history, delivery notes.
- **Scheduling** - assign deliveries to couriers with a status lifecycle.
- **Audit trail** - every meaningful action logged with user, entity, timestamp, and IP.
- **Role-based access** - admins, couriers, and customers each see a different interface.

## Background

The refillable water depot industry in Indonesia is dominated by micro-businesses. A single depot typically serves 50 to 300 households within a 3 to 5 km radius, delivering 20 to 80 gallons per day. The operational complexity is deceptively high:

- Stock must never run out, but over-ordering wastes capital.
- Deliveries must be routed efficiently or the day runs out of hours.
- Repeat customers expect to be reminded when their gallon is nearly empty.
- Payment is often partial (deposit on gallon, cash on delivery), which complicates bookkeeping.
- Staff turnover is high, so operational knowledge must live in a system, not in someone's head.

Existing POS software such as Majoo, Moka, and Pawoon targets restaurants and cafes. None of them model the gallon-deposit cycle, recurring delivery patterns, or gallon-specific stock. Galonku is purpose-built for this niche.

## Feature Status

Legend: [x] Implemented, [~] Partial, [ ] Not yet implemented.

### Authentication and Access Control

| Feature                               | Status |
| ------------------------------------- | :----: |
| JWT authentication (access + refresh) |   x    |
| Token blacklist on logout             |   x    |
| Rate limiting on login (5/min/IP)     |   x    |
| Role-based access control (3 roles)   |   x    |
| Password hashing (Argon2id / Bcrypt)  |   x    |
| Forgot password flow                  |        |
| Email verification                    |        |
| Two-factor authentication             |        |
| Silent token refresh on frontend      |        |
| Session management (list/revoke)      |        |

### Inventory and Products

| Feature                                | Status |
| -------------------------------------- | :----: |
| Product CRUD (soft delete)             |   x    |
| SKU, category, price, stock fields     |   x    |
| Low-stock detection and dashboard card |   x    |
| Atomic stock deduction per transaction |   x    |
| Stock movement history                 |        |
| Stock opname (adjustment records)      |        |
| Restock / purchase orders              |        |
| Supplier management                    |        |
| Batch / expiry tracking                |        |

### Customers

| Feature                      | Status |
| ---------------------------- | :----: |
| Customer CRUD (soft delete)  |   x    |
| Unique phone constraint      |   x    |
| Address and notes            |   x    |
| Order history per customer   |        |
| Customer segmentation / tags |        |
| Gallon deposit tracking      |        |
| Loyalty points               |        |

### Transactions

| Feature                               | Status |
| ------------------------------------- | :----: |
| Multi-item invoices                   |   x    |
| Automatic totals                      |   x    |
| Invoice number generation             |   x    |
| Status lifecycle (pending to paid)    |   x    |
| Transaction rollback on stock failure |   x    |
| Invoice PDF / print view              |        |
| Payment gateway (QRIS, VA)            |        |
| Daily / monthly reports               |        |
| Export to Excel / CSV                 |        |
| AR aging report                       |        |

### Delivery and Scheduling

| Feature                               | Status |
| ------------------------------------- | :----: |
| Schedule creation with courier        |   x    |
| Status lifecycle (pending to done)    |   x    |
| Inline status updates                 |   x    |
| Route optimization                    |        |
| Real-time courier tracking            |        |
| Proof of delivery (photo / signature) |        |
| Auto-assignment                       |        |

### Reminders and Notifications

This group of features is advertised in the repository description but is not yet implemented.

| Feature                           | Status |
| --------------------------------- | :----: |
| Consumption tracking per customer |        |
| Depletion prediction              |        |
| Automated reminders (cron)        |        |
| WhatsApp Business API integration |        |
| Email notifications               |        |
| In-app notification center        |        |
| Reorder reminder per customer     |        |

### Admin and Logging

| Feature                               | Status |
| ------------------------------------- | :----: |
| Activity log (create/update/delete)   |   x    |
| User management (activate/deactivate) |   x    |
| Role listing                          |   x    |
| Dashboard aggregate metrics           |   x    |
| Analytics charts                      |        |
| Report scheduling (email)             |        |
| Multi-depot support                   |        |

### Provisioning and Operations

| Feature                             | Status |
| ----------------------------------- | :----: |
| Nginx config example                |   x    |
| `.env.example` (backend + frontend) |   x    |
| Database migrations and seeders     |   x    |
| Docker Compose                      |        |
| Dockerfiles (backend / frontend)    |        |
| CI pipeline (GitHub Actions)        |        |
| Automated deploy                    |        |
| Backup / restore scripts            |        |
| Health check (DB-aware)             |        |
| Log rotation                        |        |
| Error tracking (Sentry)             |        |
| Uptime monitoring                   |        |

## System Architecture

Galonku is split into two independently deployable applications that communicate over HTTP/JSON.

### Components

**Frontend (React single-page application)**

- Auth context storing JWT in localStorage.
- Route guards enforcing role-based access.
- Axios client with request and response interceptors.
- Pages for dashboard, products, customers, transactions, schedules, users, and logs.

**Backend (PHP REST API)**

- Custom router with middleware pipeline.
- Middleware for authentication, role checks, and rate limiting.
- Controllers per resource (Auth, Product, Customer, Transaction, Schedule, User, Role, Log).
- Services for cross-cutting logic (AuthService, TransactionService, StockService).
- Models for data access via PDO.
- Support utilities for JWT handling, logging, validation, and response formatting.

**Database (MySQL / MariaDB)**

- Ten tables: roles, users, customers, products, transactions, transaction_items, schedules, logs, jwt_blacklist, migrations.

### Backend Request Lifecycle

1. The web server forwards all requests to `public/index.php`, except static files.
2. Dotenv loads environment variables from `.env` or `.env.testing`.
3. `App::boot()` registers the error handler, CORS headers, security headers, and the router.
4. `Router::dispatch()` matches the request path and method, then runs middleware in order.
5. The controller validates input, delegates to a service, and emits a JSON response.
6. Uncaught exceptions reach `ErrorHandler::handle()`, which maps known exception types to HTTP statuses and logs the rest.

### Frontend Data Flow

1. A page component calls a function from `src/api/*` on mount.
2. Axios attaches the JWT from localStorage and sends the request.
3. The response is stored in local component state.
4. Mutations trigger a `load()` refetch and a toast notification.

## Tech Stack

### Frontend

| Layer         | Technology                            |
| ------------- | ------------------------------------- |
| UI Framework  | React 19                              |
| Build Tool    | Vite 8 (Rolldown-powered)             |
| Routing       | React Router DOM 7                    |
| HTTP Client   | Axios 1.20                            |
| Styling       | Tailwind CSS 4 (`@tailwindcss/vite`)  |
| Icons         | Lucide React                          |
| Notifications | React Hot Toast                       |
| Linting       | ESLint 10 + React Hooks + Refresh     |
| Package Mgr   | Bun (recommended), npm/pnpm also work |

### Backend

| Layer       | Technology                     |
| ----------- | ------------------------------ |
| Language    | PHP 8.1+                       |
| Database    | MySQL 8.0+ / MariaDB 10.5+     |
| DB Access   | PDO with prepared statements   |
| Auth        | JWT HS256 (`firebase/php-jwt`) |
| Env Loading | `vlucas/phpdotenv`             |
| Logging     | `monolog/monolog`              |
| Testing     | PHPUnit                        |
| Package Mgr | Composer 2                     |

### Infrastructure

| Layer      | Technology                              |
| ---------- | --------------------------------------- |
| Web Server | Nginx, Apache, Caddy, or PHP's built-in |
| Runtime    | PHP-FPM (production)                    |
| Cache      | File-based rate limiting in `storage/`  |

## Repository Structure

This is a monorepo with two main applications.

```
pbl-trpl106-galonku/
├── frontend/                    React SPA
│   ├── public/
│   │   ├── favicon.svg
│   │   └── icons.svg
│   ├── src/
│   │   ├── api/                 Axios modules per resource
│   │   │   ├── client.js
│   │   │   ├── auth.js
│   │   │   ├── products.js
│   │   │   ├── customers.js
│   │   │   ├── transactions.js
│   │   │   ├── schedules.js
│   │   │   └── users.js
│   │   ├── components/
│   │   │   ├── Layout.jsx
│   │   │   ├── ProtectedRoute.jsx
│   │   │   └── ui.jsx
│   │   ├── contexts/
│   │   │   └── AuthContext.jsx
│   │   ├── pages/
│   │   │   ├── Login.jsx
│   │   │   ├── Register.jsx
│   │   │   ├── Dashboard.jsx
│   │   │   ├── Products.jsx
│   │   │   ├── Customers.jsx
│   │   │   ├── Transactions.jsx
│   │   │   ├── Schedules.jsx
│   │   │   ├── Users.jsx
│   │   │   └── Logs.jsx
│   │   ├── App.jsx
│   │   ├── index.css
│   │   └── main.jsx
│   ├── index.html
│   ├── package.json
│   ├── vite.config.js
│   └── README.md
│
├── backend/                     PHP REST API
│   ├── app/
│   │   ├── Controllers/Api/V1/
│   │   ├── Core/
│   │   ├── Exceptions/
│   │   ├── Middleware/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Support/
│   │   └── Testing/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   ├── seeders/
│   │   ├── Migrator.php
│   │   └── db.php
│   ├── public/
│   │   └── index.php
│   ├── routes/
│   │   └── api.php
│   ├── storage/
│   │   ├── cache/
│   │   └── logs/
│   ├── tests/
│   │   ├── Unit/
│   │   ├── Feature/
│   │   └── Integration/
│   ├── composer.json
│   └── README.md
│
├── docs/                        (planned) Diagrams, ADRs, specs
├── .gitignore
├── LICENSE
└── README.md                    (this file)
```

Each sub-project has its own detailed README:

- [`frontend/README.md`](frontend/README.md)
- [`backend/README.md`](backend/README.md)

## Getting Started

### Prerequisites

| Component | Version                     |
| --------- | --------------------------- |
| PHP       | 8.1 or newer                |
| Composer  | 2.x                         |
| MySQL     | 8.0+ or MariaDB 10.5+       |
| Node.js   | 20.19+ or 22.12+            |
| Bun       | 1.1+ (or npm / pnpm / yarn) |

Required PHP extensions: `pdo`, `pdo_mysql`, `json`, `mbstring`, `openssl`.

Verify:

```bash
php -m | grep -E 'pdo|json|mbstring|openssl'
node -v
composer --version
```

### Manual Setup

#### 1. Clone the repository

```bash
git clone https://github.com/mroczect/pbl-trpl106-galonku.git
cd pbl-trpl106-galonku
```

#### 2. Set up the backend

```bash
cd backend
composer install

cp .env.example .env
cp .env.testing.example .env.testing
```

Generate a JWT secret and paste it into both `.env` files:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Create the database and a dedicated MySQL user:

```sql
CREATE USER 'galonku'@'127.0.0.1' IDENTIFIED BY 'your_password';
CREATE USER 'galonku'@'localhost' IDENTIFIED BY 'your_password';

GRANT ALL PRIVILEGES ON galonku_db.*   TO 'galonku'@'127.0.0.1';
GRANT ALL PRIVILEGES ON galonku_db.*   TO 'galonku'@'localhost';
GRANT ALL PRIVILEGES ON galonku_test.* TO 'galonku'@'127.0.0.1';
GRANT ALL PRIVILEGES ON galonku_test.* TO 'galonku'@'localhost';

FLUSH PRIVILEGES;
```

Migrate and seed:

```bash
php database/db.php fresh --seed
```

Run the backend:

```bash
composer serve
```

The API is live at `http://localhost:8000`.

#### 3. Set up the frontend

In a new terminal:

```bash
cd frontend
bun install
```

Create `.env` (optional, defaults to localhost):

```env
VITE_API_URL=http://localhost:8000/api/v1
```

Run the dev server:

```bash
bun dev
```

Open `http://localhost:5173`.

#### 4. Log in with a demo account

| Email               | Password       | Role     |
| ------------------- | -------------- | -------- |
| `admin@galonku.com` | `admin123`     | Admin    |
| `kurir@galonku.com` | `kurir123`     | Courier  |
| `user@galonku.com`  | `pelanggan123` | Customer |

Change these passwords before any public deployment.

### Docker Setup

Not yet implemented. A `docker-compose.yml` is on the roadmap. See [Known Gaps and Roadmap](#known-gaps-and-roadmap). Once available, the intended workflow will be:

```bash
docker compose up -d
docker compose exec backend php database/db.php fresh --seed
```

Until then, use the manual setup above.

## User Roles

Galonku ships with three roles. Each role sees a different subset of the application.

### Admin

- Full access to every page and action.
- Can create, update, and soft-delete products, customers, and users.
- Can view the activity log.
- Can activate or deactivate any user account.
- Cannot self-register through the public page. Must be seeded or promoted by an existing admin.

### Kurir (Courier)

- Can view the dashboard and product catalog.
- Can create and edit customers.
- Can process transactions.
- Can view and update delivery schedules.
- Cannot delete records or access the user management or activity log pages.

### Pelanggan (Customer)

- Can view the dashboard and product catalog.
- Has no access to transactions, customers, schedules, users, or logs.
- Registered through the public `/register` endpoint.

Role enforcement happens in three places:

1. Backend route middleware (`RoleMiddleware:admin`).
2. Frontend routing (`<ProtectedRoute roles={['admin']} />`).
3. Frontend UI (the sidebar filters its menu and destructive actions only render for authorized roles).

## API Overview

Base URL: `/api/v1`. All requests and responses are JSON.

| Resource     | Endpoints                                                                                                                         |
| ------------ | --------------------------------------------------------------------------------------------------------------------------------- |
| Auth         | `POST /auth/register`, `POST /auth/login`, `POST /auth/refresh`, `GET /auth/me`, `POST /auth/logout`                              |
| Products     | `GET /products`, `GET /products/{id}`, `GET /products/low-stock`, `POST /products`, `PUT /products/{id}`, `DELETE /products/{id}` |
| Customers    | `GET /customers`, `GET /customers/{id}`, `POST /customers`, `PUT /customers/{id}`, `DELETE /customers/{id}`                       |
| Transactions | `GET /transactions`, `GET /transactions/{id}`, `POST /transactions`, `PUT /transactions/{id}/status`                              |
| Schedules    | `GET /schedules`, `GET /schedules/{id}`, `POST /schedules`, `PUT /schedules/{id}/status`                                          |
| Users        | `GET /users`, `GET /users/{id}`, `PUT /users/{id}`, `DELETE /users/{id}`                                                          |
| Roles        | `GET /roles`                                                                                                                      |
| Logs         | `GET /logs`                                                                                                                       |
| Health       | `GET /`                                                                                                                           |

### Response Shape

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

### HTTP Status Codes

| Status | Meaning                     |
| ------ | --------------------------- |
| 200    | Success                     |
| 201    | Resource created            |
| 400    | Bad request                 |
| 401    | Unauthenticated             |
| 403    | Unauthorized                |
| 404    | Not found                   |
| 409    | Conflict (unique violation) |
| 422    | Validation failed           |
| 429    | Rate limit exceeded         |
| 500    | Server error                |

Full endpoint documentation is in [`backend/README.md`](backend/README.md).

## Database Schema

Ten tables, all InnoDB, all `utf8mb4_unicode_ci`.

| Table               | Purpose                                    |
| ------------------- | ------------------------------------------ |
| `roles`             | Role definitions (admin, kurir, pelanggan) |
| `users`             | Authentication and profile                 |
| `customers`         | Customer records                           |
| `products`          | Product catalog with stock                 |
| `transactions`      | Invoice headers                            |
| `transaction_items` | Invoice line items                         |
| `schedules`         | Delivery schedules                         |
| `logs`              | Audit log entries                          |
| `jwt_blacklist`     | Revoked access token JTIs                  |
| `migrations`        | Applied migration tracking                 |

Full schema reference is in [`backend/README.md`](backend/README.md).

## Development Workflow

### Branch Naming

```
main                       Production-ready code
develop                    Integration branch
feature/<short-name>       New features
fix/<short-name>           Bug fixes
docs/<short-name>          Documentation-only
chore/<short-name>         Maintenance
```

### Commit Convention

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(products): add low-stock filter
fix(auth): handle expired refresh token
docs(readme): describe deployment steps
chore(deps): bump axios to 1.20.0
```

### Pull Request Checklist

- [ ] Branch is up to date with `develop`
- [ ] `bun run lint` passes in `frontend/`
- [ ] `composer test` passes in `backend/`
- [ ] New endpoints are documented in `backend/README.md`
- [ ] New UI primitives are documented in `frontend/README.md`
- [ ] No secrets committed to the repository
- [ ] Screenshots attached for UI changes

## Testing

### Backend

```bash
cd backend
composer test              # all suites
composer test:unit         # unit only
composer test:feat         # feature
composer test:int          # integration
composer test:cov          # coverage report
```

Individual file:

```bash
vendor/bin/phpunit tests/Feature/AuthTest.php
vendor/bin/phpunit --filter test_login_success_returns_tokens
```

Prepare the test database once:

```bash
APP_ENV=testing php database/db.php fresh --seed
```

### Frontend

```bash
cd frontend
bun run lint
```

There is no frontend test suite yet. Adding Vitest and React Testing Library is on the roadmap.

### Database Reset

```bash
cd backend
php database/db.php fresh --seed
```

Drops every table, re-runs all migrations, and re-seeds demo data.

## Deployment and Provisioning

### Backend (Production)

1. Point the web server's document root at `backend/public/`.
2. Route all non-static requests to `public/index.php`.
3. Set `.env` values:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `CORS_ALLOWED_ORIGINS=https://your-frontend-domain`
   - A strong `JWT_SECRET` (at least 32 random bytes)
4. Make `storage/` writable by PHP-FPM.
5. Run migrations: `php database/db.php migrate`.
6. Add a cron job for blacklist cleanup. Currently the cleanup is a manual SQL query (see roadmap):

   ```
   * * * * * php /path/to/backend/database/db.php cleanup:blacklist
   ```

Example Nginx configuration:

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

### Frontend (Production)

```bash
cd frontend
VITE_API_URL=https://api.example.com/api/v1 bun run build
```

Deploy the `dist/` folder to any static host.

Example Nginx SPA fallback:

```nginx
server {
    listen 80;
    server_name app.example.com;
    root /var/www/galonku-frontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|svg|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Recommended Additions

These are not yet in the repository but should be added. See the roadmap for details.

- `docker-compose.yml` for one-command local setup.
- `Dockerfile` for both applications.
- `.github/workflows/ci.yml` for automated testing.
- `Makefile` for common commands.
- `scripts/backup.sh` for database backups.

## Known Gaps and Roadmap

This section is deliberately honest about what the project does not yet have. It serves as both an acknowledgment of scope and a prioritized backlog.

### Phase 1: Provisioning (Highest Priority)

| #   | Item                                                      | Effort |
| --- | --------------------------------------------------------- | ------ |
| 1   | `docker-compose.yml` and Dockerfiles (backend + frontend) | 2 days |
| 2   | GitHub Actions CI (lint and test on PR)                   | 1 day  |
| 3   | `Makefile` with `make dev`, `make test`, `make migrate`   | 1 day  |
| 4   | `scripts/backup.sh` and cron                              | 1 day  |
| 5   | DB-aware health check (`GET /health`)                     | 1 hour |
| 6   | Log rotation for `storage/logs/app.log`                   | 1 hour |
| 7   | `docs/` folder with ERD, DFD, use case diagram            | 2 days |

### Phase 2: Advertised Features (Reminder System)

The repository description mentions automated reminders based on consumption patterns. This is not implemented yet.

| #   | Item                                                     | Effort |
| --- | -------------------------------------------------------- | ------ |
| 8   | `reminders` and `notifications` tables                   | 1 day  |
| 9   | Consumption tracking per customer (aggregate from items) | 2 days |
| 10  | Depletion prediction (average interval between orders)   | 1 day  |
| 11  | Cron job to dispatch due reminders                       | 1 day  |
| 12  | Reminder Center page in frontend                         | 3 days |
| 13  | In-app notification bell and dropdown                    | 2 days |
| 14  | WhatsApp Business API integration                        | 1 week |
| 15  | Customer self-service portal (own login)                 | 1 week |

### Phase 3: Operational Features

| #   | Item                                       | Effort |
| --- | ------------------------------------------ | ------ |
| 16  | Invoice PDF and print view                 | 3 days |
| 17  | Reports and charts (revenue, best-sellers) | 1 week |
| 18  | Gallon deposit tracking per customer       | 3 days |
| 19  | Search and filter on all list pages        | 1 week |
| 20  | Pagination controls in frontend            | 2 days |
| 21  | Stock opname (adjustment records)          | 3 days |
| 22  | Forgot password and email verification     | 3 days |
| 23  | Export to Excel / CSV                      | 2 days |

### Phase 4: Polish and Scale

| #   | Item                                          |
| --- | --------------------------------------------- |
| 24  | Dark mode                                     |
| 25  | PWA and offline support                       |
| 26  | Delivery route optimization                   |
| 27  | Payment gateway (QRIS, virtual account)       |
| 28  | Multi-depot support                           |
| 29  | Error tracking (Sentry) and uptime monitoring |
| 30  | Metrics and dashboards (Prometheus / Grafana) |
| 31  | Frontend test suite (Vitest + RTL)            |
| 32  | Accessibility audit (WCAG 2.1 AA)             |

### Quick Gap Summary

Missing entirely in provisioning:

- Docker and Compose
- CI/CD pipelines
- Backup automation
- DB-aware health check
- Log rotation
- Deployment scripts
- Secret management
- Monitoring and error tracking

Missing or partial in features:

- Consumption-based reminders (advertised but absent)
- WhatsApp and email notifications
- Invoice PDF
- Reports and analytics
- Gallon deposit tracking
- Search, filter, and pagination in the UI
- Forgot password and email verification
- Customer self-service portal
- Stock opname and movement history
- Payment gateway

## Security Notes

**Password.** Stored with `password_hash()`. Argon2id is preferred, with Bcrypt as fallback. The `password_hash` column is never returned in API responses.

**JWT.** HS256 with a shared secret. The secret must be at least 32 random bytes. Rotate by updating `.env` and restarting; existing tokens become invalid immediately.

**Token revocation.** Logout inserts the current access token's `jti` into `jwt_blacklist`. Refresh tokens are not blacklisted. Cleanup of expired entries is currently manual.

**SQL injection.** All queries use prepared statements. Column names passed to `Model::first()` and `Model::where()` are validated against `^[a-zA-Z0-9_]+$`. `paginate()` validates filter keys before interpolating into the `WHERE` clause.

**Rate limiting.** Login is limited to 5 attempts per minute per IP. State files live in `storage/cache/` and must not be publicly accessible.

**Headers.** Every response includes `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`, and `X-XSS-Protection: 1; mode=block`.

**CORS.** Controlled by `CORS_ALLOWED_ORIGINS`. Set to specific origins in production. The default `*` is development-only.

**Debug mode.** Never set `APP_DEBUG=true` in production. Level 500 responses would leak file paths, query fragments, and internal state.

**File permissions.** `storage/` and its subdirectories must be writable by the web server. Everything else should be read-only. `public/` contains only `index.php`.

**Environment files.** `.env` and `.env.testing` contain credentials. Ensure they are in `.gitignore`. Restrict permissions to `0600` in production.

## Documentation

| Document                                   | Contents                                                  |
| ------------------------------------------ | --------------------------------------------------------- |
| [`frontend/README.md`](frontend/README.md) | Frontend architecture, components, design tokens, styling |
| [`backend/README.md`](backend/README.md)   | API reference, schema, authentication, security, testing  |
| `docs/`                                    | (planned) ERD, DFD, use case diagrams, ADRs               |

## Team

PBL TRPL 106, Politeknik Negeri Batam.

| Role                 | Name           | Responsibility                       |
| -------------------- | -------------- | ------------------------------------ |
| Project Lead         | (to be filled) | Scope, coordination, final review    |
| Backend Engineer     | (to be filled) | API, database, authentication, tests |
| Frontend Engineer    | (to be filled) | UI, routing, API integration         |
| QA and Documentation | (to be filled) | Test coverage, manuals, screenshots  |

**Repository owner:** [@mroczect](https://github.com/mroczect)

**Supervising Lecturer:** (to be filled)

## Contributing

1. Fork the repository.
2. Create a feature branch: `git checkout -b feature/your-feature`.
3. Commit with a conventional message: `git commit -m "feat(scope): description"`.
4. Push to your branch: `git push origin feature/your-feature`.
5. Open a Pull Request against `develop`.

Make sure lint and tests pass before requesting review. See the pull request checklist above.

## License

This project is released under GPL-3.0-only. See the [`LICENSE`](LICENSE) file for the full text.

The GPL was chosen deliberately. This is a public-interest project developed as part of an academic program, and any derivative work, including commercial forks, must remain open. If you deploy Galonku for your own depot, we ask that you contribute improvements back so other depots benefit.
