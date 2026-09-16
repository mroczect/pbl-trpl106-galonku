# Galonku Backend API

Backend API for a drinking water depot management application.

## Setup

```bash
composer install
cp .env.example .env
# Edit .env -> set JWT_SECRET to random length
php database/migrate.php
composer serve
```

The API is running at `http://localhost:8000/api/v1`.

## Primary Endpoint

- `POST /api/v1/auth/register`

- `POST /api/v1/auth/login`

- `POST /api/v1/auth/refresh`

- `GET /api/v1/auth/me`

- `POST /api/v1/auth/logout`

- `GET /api/v1/products`

- `GET /api/v1/customers`

- `GET /api/v1/transactions`

- `GET /api/v1/schedules`