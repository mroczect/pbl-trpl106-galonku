#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../backend/vendor/autoload.php';

use Dotenv\Dotenv;

$appEnv  = getenv('APP_ENV') ?: 'local';
$envFile = $appEnv === 'testing' ? '.env.testing' : '.env';
$envPath = dirname(__DIR__) . '/backend';

if (!file_exists("$envPath/$envFile")) {
    fwrite(STDERR, "Missing $envFile in $envPath\n");
    fwrite(STDERR, "Copy from template: cp .env.example $envFile\n");
    exit(1);
}

Dotenv::createImmutable($envPath, $envFile)->load();

$command = $argv[1] ?? 'help';
$args    = array_slice($argv, 2);

try {
    require __DIR__ . '/Migrator.php';

    $migrator = new Migrator(
        __DIR__ . '/migrations',
        __DIR__ . '/seeders'
    );

    switch ($command) {
        case 'migrate':
            $migrator->migrate();
            break;

        case 'rollback':
            $steps = isset($args[0]) ? (int) $args[0] : 1;
            $migrator->rollback($steps);
            break;

        case 'fresh':
            $migrator->fresh(in_array('--seed', $args, true));
            break;

        case 'reset':
            $migrator->reset(in_array('--seed', $args, true));
            break;

        case 'seed':
            $migrator->seed($args[0] ?? null);
            break;

        case 'status':
            $migrator->status();
            break;

        case 'drop':
            if (($args[0] ?? '') !== '--force') {
                fwrite(STDERR, "Add --force to confirm.\n");
                exit(1);
            }
            $migrator->dropDatabase();
            break;

        case 'help':
        default:
            echo <<<HELP
Usage: php database/db.php <command> [options]

Commands:
  migrate              Run pending migrations
  rollback [n]         Roll back the last n batches (default: 1)
  fresh [--seed]       Drop all tables, re-migrate, optionally seed
  reset [--seed]       Roll back all, re-migrate, optionally seed
  seed [name]          Run all seeders, or a specific one
  status               Show migration and seeder status
  drop --force         Drop the database

Examples:
  php database/db.php migrate
  php database/db.php fresh --seed
  php database/db.php seed RoleSeeder
  php database/db.php status

Environment:
  APP_ENV=testing php database/db.php fresh --seed

HELP;
            break;
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        fwrite(STDERR, $e->getTraceAsString() . "\n");
    }
    exit(1);
}
