# Galonku Database CLI

A standalone PHP CLI for managing the schema and seed data of the Galonku backend. No framework. Reads its config from `backend/config/database.php` and `backend/.env`. Talks to MySQL/MariaDB through PDO.

The tool is split into two responsibilities:

- **Migrations** — versioned, ordered, reversible DDL. One file per table. Tracked in a `migrations` table with batch numbers.
- **Seeders** — idempotent data loading. Ordered by an explicit `priority()` value, not alphabetically.

Everything runs from `php database/db.php <command>`.

---

## Table of Contents

- [Requirements](#requirements)
- [Configuration](#configuration)
- [CLI Commands](#cli-commands)
- [Database Schema](#database-schema)
- [Writing Migrations](#writing-migrations)
- [Writing Seeders](#writing-seeders)
- [How the Engine Works](#how-the-engine-works)
- [Maintenance Scripts](#maintenance-scripts)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## Requirements

| Component | Version               |
| --------- | --------------------- |
| PHP       | 8.2 or newer          |
| MySQL     | 8.0+                  |
| MariaDB   | 10.6+                 |
| Composer  | 2.x (for the backend) |

The CLI depends on the backend's Composer autoloader for `vlucas/phpdotenv`. Run `composer install` in `backend/` before using `db.php`.

Required PHP extensions: `pdo`, `pdo_mysql`, `json`, `mbstring`.

---

## Configuration

Database credentials come from two places, in this order:

1. **Shell environment** — `DB_DATABASE` and other vars exported in your shell. Checked first.
2. **`.env` file** — loaded from `backend/.env` or `backend/.env.testing`, depending on `APP_ENV`.

The bootstrap picks the env file like this:

```php
$envName = (getenv('APP_ENV') ?: 'local') === 'testing' ? '.env.testing' : '.env';
```

So `APP_ENV=testing php database/db.php ...` loads `backend/.env.testing`. Anything else loads `backend/.env`.

### Environment Variables

| Variable                 | Default      | Notes                                                        |
| ------------------------ | ------------ | ------------------------------------------------------------ |
| `APP_ENV`                | `local`      | `local`, `testing`, or `production`. Drives env file choice. |
| `APP_DEBUG`              | `false`      | When `true`, CLI prints stack traces on failure.             |
| `DB_HOST`                | `127.0.0.1`  | MySQL host                                                   |
| `DB_PORT`                | `3306`       | MySQL port                                                   |
| `DB_DATABASE`            | `galonku_db` | Target database. Validated against `^[a-zA-Z0-9_]+$`.        |
| `DB_USERNAME`            | `root`       | MySQL user                                                   |
| `DB_PASSWORD`            | _(empty)_    | MySQL password                                               |
| `DB_CHARSET`             | `utf8mb4`    | Connection charset                                           |
| `SEED_ADMIN_PASSWORD`    | –            | Required by `UserSeeder` when `APP_ENV=production`           |
| `SEED_AGENT_PASSWORD`    | –            | Optional production override for the agent account           |
| `SEED_CUSTOMER_PASSWORD` | –            | Optional production override for the customer account        |

The database config itself lives in `backend/config/database.php`. The bootstrap reads it via `Database\Connection::config()`.

---

## CLI Commands

All commands go through `php database/db.php`. No arguments defaults to `help`.

```
Usage: php database/db.php <command> [options]
```

| Command          | What it does                                                         |
| ---------------- | -------------------------------------------------------------------- |
| `migrate`        | Run every pending migration in a new batch                           |
| `rollback [n]`   | Roll back the last `n` batches. Default `1`. Must be a positive int. |
| `fresh [--seed]` | Drop all tables, re-run all migrations, optionally seed              |
| `reset [--seed]` | Roll back every batch, re-run all migrations, optionally seed        |
| `seed [name]`    | Run every seeder, or one specific seeder by class name               |
| `status`         | Show which migrations ran and which are pending, plus all seeders    |
| `drop --force`   | Drop the entire database. Requires `--force`.                        |
| `help`           | Print usage                                                          |

### Examples

```bash
# Normal flow
php database/db.php migrate
php database/db.php seed
php database/db.php status

# Full rebuild from scratch with seed data
php database/db.php fresh --seed

# Rebuild keeping the current data
php database/db.php reset --seed

# Roll back the last two batches
php database/db.php rollback 2

# Run a single seeder
php database/db.php seed RoleSeeder

# Drop the database (needs the flag)
php database/db.php drop --force

# Testing environment
APP_ENV=testing php database/db.php fresh --seed
```

### Composer Shortcuts

From `backend/`, these wrappers exist:

```bash
composer db:migrate
composer db:fresh
composer db:reset
composer db:seed
composer db:status
composer db:rollback
composer db:test:fresh    # APP_ENV=testing php ../database/db.php fresh --seed
composer db:drop
```

The scripts assume the CLI lives one directory up (`../database/db.php`), which matches the repository layout.

### Exit Codes

| Code | Meaning                                   |
| ---- | ----------------------------------------- |
| `0`  | Success                                   |
| `1`  | Command failed (exception, invalid input) |

Every command that throws prints the error message to stderr and returns `1`. With `APP_DEBUG=true` the stack trace is printed too.

---

## Database Schema

Eleven application tables plus the `migrations` metadata table. All InnoDB, all `utf8mb4_unicode_ci` unless noted.

### `roles`

Reference table for user roles.

| Column      | Type         | Notes                           |
| ----------- | ------------ | ------------------------------- |
| id          | INT UNSIGNED | PK, auto-increment              |
| name        | VARCHAR(50)  | Unique                          |
| description | VARCHAR(255) | Nullable                        |
| created_at  | TIMESTAMP    | Defaults to `CURRENT_TIMESTAMP` |
| updated_at  | TIMESTAMP    | Auto-updates on row change      |

### `users`

Application accounts. Belongs to a role.

| Column        | Type         | Notes                                                      |
| ------------- | ------------ | ---------------------------------------------------------- |
| id            | INT UNSIGNED | PK, auto-increment                                         |
| role_id       | INT UNSIGNED | FK → `roles.id`, `ON UPDATE CASCADE`, `ON DELETE RESTRICT` |
| name          | VARCHAR(100) |                                                            |
| email         | VARCHAR(150) | Unique                                                     |
| phone         | VARCHAR(20)  | Nullable                                                   |
| password_hash | VARCHAR(255) | Argon2id or Bcrypt hash                                    |
| is_active     | TINYINT(1)   | Default 1                                                  |
| last_login_at | TIMESTAMP    | Nullable                                                   |
| created_at    | TIMESTAMP    | Defaults to `CURRENT_TIMESTAMP`                            |
| updated_at    | TIMESTAMP    | Auto-updates on row change                                 |

Indexes on `role_id` and `is_active`.

### `customers`

People and businesses that buy from the depot.

| Column     | Type         | Notes                           |
| ---------- | ------------ | ------------------------------- |
| id         | INT UNSIGNED | PK, auto-increment              |
| name       | VARCHAR(100) |                                 |
| phone      | VARCHAR(20)  | Unique                          |
| address    | TEXT         | Nullable                        |
| notes      | TEXT         | Nullable                        |
| is_active  | TINYINT(1)   | Default 1                       |
| created_at | TIMESTAMP    | Defaults to `CURRENT_TIMESTAMP` |
| updated_at | TIMESTAMP    | Auto-updates on row change      |

Indexes on `is_active` and `name`.

### `products`

Items the depot sells. Categories are an ENUM: `galon`, `air`, `aksesoris`, `lain`.

| Column     | Type          | Notes                                                 |
| ---------- | ------------- | ----------------------------------------------------- |
| id         | INT UNSIGNED  | PK, auto-increment                                    |
| sku        | VARCHAR(50)   | Unique                                                |
| name       | VARCHAR(150)  |                                                       |
| category   | ENUM          | `galon`, `air`, `aksesoris`, `lain`. Default `galon`. |
| price      | DECIMAL(12,2) | Default 0                                             |
| stock      | INT           | Default 0. CHECK constraint `stock >= 0`.             |
| is_active  | TINYINT(1)    | Default 1                                             |
| created_at | TIMESTAMP     | Defaults to `CURRENT_TIMESTAMP`                       |
| updated_at | TIMESTAMP     | Auto-updates on row change                            |

Indexes on `category`, `is_active`, `stock`.

### `transactions`

Sale headers. One row per invoice.

| Column       | Type          | Notes                                                          |
| ------------ | ------------- | -------------------------------------------------------------- |
| id           | INT UNSIGNED  | PK, auto-increment                                             |
| invoice_no   | VARCHAR(30)   | Unique                                                         |
| customer_id  | INT UNSIGNED  | FK → `customers.id`, `ON UPDATE CASCADE`, `ON DELETE RESTRICT` |
| user_id      | INT UNSIGNED  | FK → `users.id`, `ON UPDATE CASCADE`, `ON DELETE RESTRICT`     |
| type         | ENUM          | `sale`, `delivery`, `return`. Default `sale`.                  |
| total_amount | DECIMAL(14,2) | Default 0                                                      |
| paid_amount  | DECIMAL(14,2) | Default 0                                                      |
| status       | ENUM          | `pending`, `paid`, `partial`, `cancelled`. Default `pending`.  |
| notes        | TEXT          | Nullable                                                       |
| created_at   | TIMESTAMP     | Defaults to `CURRENT_TIMESTAMP`                                |
| updated_at   | TIMESTAMP     | Auto-updates on row change                                     |

Indexes on `customer_id`, `user_id`, `status`, `type`, `created_at`.

### `transaction_items`

Line items. Cascade-deletes when the parent transaction is removed.

| Column         | Type          | Notes                                       |
| -------------- | ------------- | ------------------------------------------- |
| id             | INT UNSIGNED  | PK, auto-increment                          |
| transaction_id | INT UNSIGNED  | FK → `transactions.id`, `ON DELETE CASCADE` |
| product_id     | INT UNSIGNED  | FK → `products.id`, `ON DELETE RESTRICT`    |
| qty            | INT           | CHECK `qty > 0`                             |
| unit_price     | DECIMAL(12,2) |                                             |
| subtotal       | DECIMAL(14,2) |                                             |

Indexes on `transaction_id` and `product_id`.

### `schedules`

Delivery schedules. Ties a customer to an agent at a specific time.

| Column       | Type         | Notes                                                          |
| ------------ | ------------ | -------------------------------------------------------------- |
| id           | INT UNSIGNED | PK, auto-increment                                             |
| customer_id  | INT UNSIGNED | FK → `customers.id`, `ON DELETE RESTRICT`                      |
| user_id      | INT UNSIGNED | FK → `users.id`, `ON DELETE RESTRICT`                          |
| scheduled_at | DATETIME     |                                                                |
| status       | ENUM         | `pending`, `on_route`, `done`, `cancelled`. Default `pending`. |
| notes        | TEXT         | Nullable                                                       |
| created_at   | TIMESTAMP    | Defaults to `CURRENT_TIMESTAMP`                                |
| updated_at   | TIMESTAMP    | Auto-updates on row change                                     |

Indexes on `customer_id`, `user_id`, `status`, `scheduled_at`.

### `stock_movements`

Audit log for every stock change. Populated by `StockService` and `TransactionService`. See the backend README for how the API writes to this table.

| Column         | Type         | Notes                                           |
| -------------- | ------------ | ----------------------------------------------- |
| id             | INT UNSIGNED | PK, auto-increment                              |
| product_id     | INT UNSIGNED | FK → `products.id`, `ON DELETE RESTRICT`        |
| user_id        | INT UNSIGNED | FK → `users.id`, `ON DELETE SET NULL`, nullable |
| type           | ENUM         | `in`, `out`, `adjustment`                       |
| qty            | INT          | Always positive; `type` gives direction         |
| stock_before   | INT          | Snapshot before the change                      |
| stock_after    | INT          | Snapshot after the change                       |
| reason         | VARCHAR(100) | Short human-readable label                      |
| reference_type | VARCHAR(50)  | Nullable. E.g. `transaction`.                   |
| reference_id   | INT UNSIGNED | Nullable                                        |
| notes          | TEXT         | Nullable                                        |
| created_at     | TIMESTAMP    | Defaults to `CURRENT_TIMESTAMP`                 |

Indexes on `product_id`, `type`, `user_id`, `created_at`. No index on `(reference_type, reference_id)` — add one if you query by source record frequently.

### `logs`

Audit trail for user actions. Also written by `AppLogger::action()` in the backend.

| Column     | Type            | Notes                                                       |
| ---------- | --------------- | ----------------------------------------------------------- |
| id         | BIGINT UNSIGNED | PK, auto-increment                                          |
| user_id    | INT UNSIGNED    | FK → `users.id`, `ON DELETE SET NULL`, nullable             |
| action     | VARCHAR(100)    | `register`, `login`, `logout`, `create`, `update`, `delete` |
| entity     | VARCHAR(100)    | Nullable                                                    |
| entity_id  | INT UNSIGNED    | Nullable                                                    |
| payload    | JSON            | Nullable                                                    |
| ip_address | VARCHAR(45)     | Nullable. IPv4 or IPv6.                                     |
| created_at | TIMESTAMP       | Defaults to `CURRENT_TIMESTAMP`                             |

Indexes on `user_id`, `action`, `(entity, entity_id)`, `created_at`.

### `jwt_blacklist`

Revoked JWT IDs. Rows are keyed by the `jti` claim. Cleaned up by `cleanup.php`.

| Column     | Type            | Notes                             |
| ---------- | --------------- | --------------------------------- |
| id         | BIGINT UNSIGNED | PK, auto-increment                |
| jti        | VARCHAR(64)     | Unique                            |
| expires_at | DATETIME        | When the token would have expired |
| created_at | TIMESTAMP       | Defaults to `CURRENT_TIMESTAMP`   |

Index on `expires_at` for the cleanup query.

### `migrations`

Metadata for the migrator. Not an application table.

| Column      | Type         | Notes                                        |
| ----------- | ------------ | -------------------------------------------- |
| id          | INT UNSIGNED | PK, auto-increment                           |
| migration   | VARCHAR(255) | Unique. The migration's class name.          |
| batch       | INT UNSIGNED | Batch number, incremented per `migrate` call |
| executed_at | TIMESTAMP    | Defaults to `CURRENT_TIMESTAMP`              |

Index on `batch`.

---

## Writing Migrations

### File Layout

Migrations live in `database/migrations/`. Each file:

- Is named `<YYYY_MM_DD_HHMMSS>_<description>.php`
- Returns an anonymous class that extends `Database\Migration\Migration`
- Implements `up(PDO $pdo): void`
- Optionally implements `down(PDO $pdo): void` (the base class throws if you try to roll back a migration without a `down()`)

Files whose name starts with an underscore (`_base.php`, `_helpers.php`) are skipped by the runner. Use this for shared base classes.

### Minimal Example

```php
<?php
declare(strict_types=1);

use Database\Migration\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->createTable($pdo, 'tags', fn() => "
            CREATE TABLE `tags` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(100) NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_tags_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'tags');
    }
};
```

### Helpers on the Base Class

| Method                                           | What it does                                                |
| ------------------------------------------------ | ----------------------------------------------------------- |
| `createTable(PDO, string $table, callable $ddl)` | Runs `$ddl()` only if `$table` doesn't exist. Idempotent.   |
| `dropTable(PDO, string $table)`                  | `DROP TABLE IF EXISTS`. Quotes the identifier.              |
| `hasTable(PDO, string $table)`                   | Returns `true` if the table exists in the current database. |

`createTable` checks `information_schema.tables` for `table_schema = DATABASE()`. Running `migrate` twice on the same file is safe.

### Timestamps

Use UTC-based timestamps that sort lexicographically. The recommended format is `YYYY_MM_DD_HHMMSS_<description>.php`. The runner sorts alphabetically, so the timestamp is what determines order.

If two migrations have identical timestamps, the alphabetical sort on the rest of the filename breaks the tie. Don't rely on this — keep timestamps unique.

### Foreign Keys

Give every FK constraint an explicit name. The convention used here is:

- `fk_<table>_<referenced_table>` for foreign keys
- `uk_<table>_<column>` for unique keys
- `idx_<table>_<column>` for regular indexes
- `ck_<table>_<description>` for CHECK constraints

This matters during rollback and schema inspection — MySQL generates its own names if you don't supply one, and those names vary between servers.

### Transactions and DDL

MySQL and MariaDB **auto-commit DDL statements**. `CREATE TABLE`, `ALTER TABLE`, and `DROP TABLE` cannot be rolled back even inside an explicit transaction. The runner still wraps each migration in `beginTransaction()`/`commit()` — it's harmless and catches errors from any DML a migration might do — but do not assume a failed `ALTER` will roll back.

If a migration fails halfway through a multi-statement `up()`, the database is left in a partial state. The `migrations` table won't record the migration, so re-running `migrate` will attempt it again. Make each migration idempotent (`createTable` helps) or split risky changes into smaller files.

### Adding a Column

`createTable` only handles table creation. For schema changes on existing tables, use raw `ALTER TABLE` statements:

```php
public function up(PDO $pdo): void
{
    $pdo->exec("
        ALTER TABLE `products`
        ADD COLUMN `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00
        AFTER `price`
    ");
}

public function down(PDO $pdo): void
{
    $pdo->exec("ALTER TABLE `products` DROP COLUMN `cost_price`");
}
```

There is no helper for column existence — write the check yourself if you want idempotency:

```php
$exists = $pdo->prepare("
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
");
$exists->execute(['products', 'cost_price']);
if (!$exists->fetchColumn()) {
    $pdo->exec("ALTER TABLE `products` ADD COLUMN ...");
}
```

---

## Writing Seeders

### File Layout

Seeders live in `database/seeders/`. Each file:

- Is named `<Name>Seeder.php`
- Returns an anonymous class that extends `Database\Seeder\Seeder`
- Implements `run(PDO $pdo): void`
- Optionally overrides `priority(): int`

The runner discovers files matching `*Seeder.php` and skips the base `Seeder.php` itself.

### Minimal Example

```php
<?php
declare(strict_types=1);

use Database\Seeder\Seeder;

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $stmt = $pdo->prepare(
            "INSERT INTO tags (name) VALUES (?)
             ON DUPLICATE KEY UPDATE name = VALUES(name)"
        );
        $stmt->execute(['galon']);
    }

    public function priority(): int
    {
        return 25;
    }
};
```

### Priority

Priority is a plain integer. Lower numbers run first. The default is `100`.

Current ordering:

| Priority | Seeder           | Depends on                       |
| -------- | ---------------- | -------------------------------- |
| 10       | `RoleSeeder`     | –                                |
| 20       | `UserSeeder`     | `roles`                          |
| 30       | `ProductSeeder`  | –                                |
| 40       | `CustomerSeeder` | –                                |
| 50       | `DemoSeeder`     | `users`, `products`, `customers` |

If two seeders share a priority, they fall back to alphabetical comparison of their filenames.

To insert a new seeder between two existing ones, pick a number between their priorities. To run at the end, use anything above 50. To run before everything, use anything below 10.

### Idempotency

Seeders run on every `fresh --seed` and on every `seed` command. They must be safe to run repeatedly. Two patterns work:

**Pattern 1 — `ON DUPLICATE KEY UPDATE`** (used by `RoleSeeder`, `UserSeeder`, `ProductSeeder`, `CustomerSeeder`):

```sql
INSERT INTO products (sku, name, price) VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price)
```

Requires a unique key on the natural identifier (SKU, email, phone, role name).

**Pattern 2 — check-then-insert** (used by `DemoSeeder` for the transaction):

```php
$check = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE invoice_no = ?');
$check->execute(['INV-DEMO-0001']);
if ((int) $check->fetchColumn() > 0) {
    return;
}
```

Use this when the row can't be keyed on a natural unique value, or when you need to skip side effects (like stock decrements) that shouldn't be applied twice.

### Production Safety

`UserSeeder` refuses to run when `APP_ENV=production` unless `SEED_ADMIN_PASSWORD` is set:

```php
if ($isProd) {
    $admin = getenv('SEED_ADMIN_PASSWORD');
    if ($admin === false || $admin === '') {
        throw new \RuntimeException('Refusing to seed users in production. ...');
    }
    $users[0]['password'] = $admin;
    // ...
}
```

Apply the same pattern to any seeder that creates users or otherwise ships credentials. `APP_ENV` is read from the shell, not `.env`, so set it explicitly:

```bash
APP_ENV=production php database/db.php seed UserSeeder
```

Note that `RoleSeeder` and `UserSeeder` use `$pdo->prepare()` directly in `run()`. They don't wrap the whole thing in a transaction, so a mid-loop failure can leave a partial set of rows. Each `execute()` call is atomic but the seeder as a whole is not. If you need all-or-nothing semantics, wrap `run()` manually:

```php
public function run(PDO $pdo): void
{
    $pdo->beginTransaction();
    try {
        // ... inserts
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
```

`DemoSeeder` already does this.

---

## How the Engine Works

### Bootstrap

`database/bootstrap.php` is required first by both `db.php` and `cleanup.php`. It:

1. Registers an autoloader for the `Database\` namespace, mapping to `database/src/`.
2. Requires `backend/vendor/autoload.php` for Composer packages.
3. Loads `.env` from `backend/` if `DB_DATABASE` isn't already set in the environment.

Because the autoloader is registered manually rather than through Composer, the `Database\` classes work even though `composer.json` doesn't list them. Don't add them to Composer's PSR-4 map — it will conflict.

### Migration Runner

The runner (`Database\Migration\Runner`) does five things:

1. **Discovers migrations.** Globs `*.php` in the migration directory, strips the extension, skips files starting with `_`, and sorts alphabetically.
2. **Diffs against the `migrations` table.** Anything in the directory that isn't recorded in the table is pending.
3. **Acquires a MySQL advisory lock.** `SELECT GET_LOCK('galonku_migrator', 5)`. If another process holds the lock, it fails with a clear error rather than corrupting state.
4. **Runs each pending migration.** Wraps each in `beginTransaction()` (best-effort — DDL auto-commits), calls `up()`, then records the file name and the batch number in `migrations`.
5. **Releases the lock** when the PDO connection closes.

Batch numbers are monotonic — `MAX(batch) + 1` across the whole `migrations` table. `rollback n` picks the last `n` distinct batches and reverses them in descending order of batch, then descending insertion order within the batch. So migrations roll back in reverse of the order they ran.

If a migration's `down()` throws, the runner stops and returns a non-zero exit code. Remaining batches are not touched.

### Seeder Runner

The seeder runner (`Database\Seeder\Runner`) does four things:

1. **Discovers seeders.** Globs `*Seeder.php`, skips the abstract `Seeder.php`, and reads each file once to instantiate the anonymous class.
2. **Sorts.** Calls `priority()` on each instance, sorts ascending. Ties break on filename.
3. **Runs each in order.** If one seeder throws, the runner logs the failure and moves to the next. It does not abort.
4. **Reports the count.** Returns the number of seeders that ran successfully.

The "keep going on failure" behavior is intentional — if `DemoSeeder` fails because a prerequisite wasn't met, you don't want it to prevent `RoleSeeder` from finishing. Check the exit code and the printed summary.

### Idempotency of `migrate`

The migrator is safe to run repeatedly. The `migrations` table tracks what ran; already-run files are skipped. `createTable` also checks `information_schema` before issuing DDL, so even a migration that runs twice on the same schema won't fail.

### The `fresh` Command

`fresh` does the following, in order:

1. `SET FOREIGN_KEY_CHECKS = 0` on the connection.
2. Queries `information_schema.tables` for all base tables in the current database.
3. `DROP TABLE IF EXISTS` each one, printing its name.
4. `SET FOREIGN_KEY_CHECKS = 1`.
5. Calls `ensureTable()` to recreate `migrations`.
6. Runs all migrations.
7. Optionally seeds.

The `FOREIGN_KEY_CHECKS` toggle is inside a `try/finally` — if a drop throws, the flag is restored before the exception propagates.

Note that `fresh` reads table names from `information_schema`, not from a hardcoded list. Any table you created outside the migrations system (via manual SQL, a different tool, etc.) will also be dropped. This is intentional but worth knowing.

### The `reset` Command

`reset` rolls back every batch, then re-runs all migrations. Because rollback uses each migration's `down()`, this is only as reliable as your `down()` implementations. If a `down()` is missing or wrong, `reset` will leave you in a worse state than `fresh`.

Use `fresh` when you don't care about data. Use `reset` only if you've tested the rollback path.

---

## Maintenance Scripts

### `cleanup.php`

Prunes expired JWT blacklist rows and stale rate-limit cache files. Safe to run on a schedule.

```bash
php database/cleanup.php
```

What it does:

1. `DELETE FROM jwt_blacklist WHERE expires_at < NOW()`. Prints the deleted count.
2. Walks `backend/storage/cache/` and deletes files matching `rl_*.json` that haven't been modified in the last hour. Prints the removed count.

The rate-limit cache directory is created by the API at request time; if it doesn't exist, the script exits quietly.

Suggested cron entry:

```cron
0 * * * * cd /path/to/project && php database/cleanup.php >> /var/log/galonku-cleanup.log 2>&1
```

Run it hourly. The rate limit window is 60 seconds by default, so an hour of retention is more than enough to avoid deleting live buckets.

### Database Dumps

The CLI doesn't include a backup command. Use `mysqldump` directly:

```bash
mysqldump -u galonku -p galonku_db > galonku_db_$(date +%F).sql
```

Restore:

```bash
mysql -u galonku -p galonku_db < galonku_db_2026-09-20.sql
```

There's a `tools/` directory in the repository containing a Rust scaffold. It's not wired into the CLI and has no current functionality. Ignore it unless you're actively developing tooling in Rust.

---

## Troubleshooting

### `Missing backend/vendor/autoload.php — run composer install`

The bootstrap requires the backend's Composer autoloader before doing anything else. Run:

```bash
cd backend && composer install
```

### `Database config not found: .../backend/config/database.php`

The config file is missing or the path is wrong. Confirm the file exists at `backend/config/database.php` and returns an array.

### `Unsafe database name: <name>`

`DB_DATABASE` contains characters outside `[a-zA-Z0-9_]`. The guard rejects anything else to prevent SQL injection through the database name (which can't be parameterized). Rename the database or fix the env var.

### `Another migration/seeder process is running. Try again later.`

Another `db.php` process holds the `galonku_migrator` lock. This happens when:

- A previous run crashed without releasing the lock (rare — the lock releases when the connection closes).
- Two deploy scripts run `migrate` simultaneously.

If the lock is stuck, check `SHOW PROCESSLIST` for orphaned connections, or wait 5 seconds and retry.

### `Migration <name> must return a Database\Migration\Migration instance`

The file doesn't return the anonymous class — usually because of a missing `return` or a syntax error that made PHP silently return `null`. Check the file's last statement.

### `Seeder not found: <name>. Available: ...`

You passed a class name to `seed` that doesn't exist. Seeder names are the file basenames without `.php`. `RoleSeeder.php` → `RoleSeeder`. Case-sensitive.

### `Refusing to seed users in production. ...`

`UserSeeder` ran with `APP_ENV=production` but `SEED_ADMIN_PASSWORD` wasn't set. Either set it, or use `APP_ENV=local` if you're not actually on production.

### `DemoSeeder requires at least 2 customers, 2 users, and 2 demo products`

`DemoSeeder` depends on the earlier seeders having run. If you ran `seed DemoSeeder` alone, run the full set first:

```bash
php database/db.php fresh --seed
```

Or run them in priority order manually:

```bash
php database/db.php seed RoleSeeder
php database/db.php seed UserSeeder
php database/db.php seed ProductSeeder
php database/db.php seed CustomerSeeder
php database/db.php seed DemoSeeder
```

### Migration fails with a foreign key violation

You're creating a table that references a table that doesn't exist yet. Check that the referenced migration has a lower timestamp. The runner sorts alphabetically by filename, so `2026_09_17_000002` runs before `2026_09_17_000003`.

### `fresh` drops a table you didn't expect

`fresh` drops every base table in the current database, not just the ones the migrations create. If you've added side tables manually, they'll be dropped too. Back up anything you can't regenerate.

### Seeder runs but data doesn't appear

`ON DUPLICATE KEY UPDATE` only updates the columns listed. If you changed a value that isn't in the `UPDATE` clause, the existing row keeps its old value. Check the seeder's SQL — most of them only update name/description/price, not stock or is_active.

### Colors don't render in CI logs

`Output::decorated()` checks `stream_isatty(STDOUT)` and `NO_COLOR`. In CI, stdout is usually not a TTY, so colors are disabled automatically. If your CI still shows escape codes, set `NO_COLOR=1` in the environment.

---

## License

GPL-3.0-only. See the `LICENSE` file at the repository root.
