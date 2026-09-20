# Galonku Database CLI

A PHP CLI tool for managing MySQL database migrations and seeders.

## Prerequisites

- PHP 8.2+
- MySQL 8.0+ / MariaDB 10.6+
- Composer (requires `vlucas/phpdotenv` from `backend/vendor/`)

## Configuration

Database credentials are loaded from `backend/config/database.php` and `.env`.
Key environment variables:

- `APP_ENV`: `local`, `testing`, or `production`
- `DB_DATABASE`: Override database name
- `SEED_ADMIN_PASSWORD`: Required if seeding in production

## CLI Usage

Run commands via `php database/db.php <command>`.

| Command          | Description                                   |
| ---------------- | --------------------------------------------- |
| `migrate`        | Run pending migrations.                       |
| `rollback [n]`   | Rollback the last `n` batches (default: 1).   |
| `fresh [--seed]` | Drop all tables, re-migrate, optionally seed. |
| `reset [--seed]` | Rollback all, re-migrate, optionally seed.    |
| `seed [Name]`    | Run all seeders or a specific seeder class.   |
| `status`         | Show migration and seeder status.             |
| `drop --force`   | Drop the entire database.                     |

## Database Schema

The migrations create the following tables:
`roles`, `users`, `customers`, `products`, `transactions`, `transaction_items`, `schedules`, `logs`, `jwt_blacklist`, and `migrations` (metadata).

## Creating Migrations

1. Create a file in `migrations/` named `YYYY_MM_DD_HHMMSS_description.php`.
2. Return an anonymous class extending `Database\Migration\Migration`.

```php
<?php
use Database\Migration\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void {
        $this->createTable($pdo, 'tags', fn() => "
            CREATE TABLE `tags` (`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
        ");
    }
    public function down(PDO $pdo): void {
        $this->dropTable($pdo, 'tags');
    }
};
```

## Creating Seeders

1. Create a file in `seeders/` named `NameSeeder.php`.
2. Return an anonymous class extending `Database\Seeder\Seeder`.
3. Define a `priority()` method (lower numbers run first).

```php
<?php
use Database\Seeder\Seeder;

return new class extends Seeder {
    public function run(PDO $pdo): void {
        // Insert data here
    }
    public function priority(): int {
        return 10; // Runs before priority 20
    }
};
```
