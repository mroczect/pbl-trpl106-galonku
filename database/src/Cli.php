<?php
declare(strict_types=1);

namespace Database;

use Database\Support\Output;

final class Cli
{
    public function __construct(private Migrator $migrator) {}

    public function run(array $argv): int
    {
        $command = $argv[1] ?? 'help';
        $args    = array_slice($argv, 2);

        try {
            $exit = match ($command) {
                'migrate'              => $this->migrator->migrate(),
                'rollback'             => $this->migrator->rollback($this->parseSteps($args)),
                'fresh'                => $this->migrator->fresh(in_array('--seed', $args, true)),
                'reset'                => $this->migrator->reset(in_array('--seed', $args, true)),
                'seed'                 => $this->migrator->seed($args[0] ?? null),
                'status'               => $this->migrator->status(),
                'drop'                 => $this->drop($args),
                'help', '-h', '--help' => $this->help(),
                default                => $this->unknown($command),
            };
            return is_int($exit) ? $exit : 0;
        } catch (\Throwable $e) {
            Output::fail($e->getMessage());
            if (($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'false') === 'true') {
                Output::line($e->getTraceAsString());
            }
            return 1;
        }
    }

    private function parseSteps(array $args): int
    {
        $raw = $args[0] ?? '1';
        if (!is_string($raw) || !ctype_digit($raw) || (int) $raw < 1) {
            throw new \InvalidArgumentException(
                "Invalid rollback steps: '{$raw}'. Expected a positive integer."
            );
        }
        return (int) $raw;
    }

    private function drop(array $args): int
    {
        if (!in_array('--force', $args, true)) {
            Output::fail('Add --force to confirm this destructive action.');
            return 1;
        }
        return $this->migrator->dropDatabase();
    }

    private function unknown(string $command): int
    {
        Output::fail("Unknown command: {$command}");
        $this->help();
        return 1;
    }

    private function help(): int
    {
        Output::line(<<<TXT

            Database CLI — Galonku
            ───────────────────────
            Usage: php database/db.php <command> [options]

            Commands:
              migrate           Run all pending migrations
              rollback [n]      Roll back the last n batches (default: 1)
              fresh [--seed]    Drop all tables, re-migrate, optionally seed
              reset [--seed]    Roll back all, re-migrate, optionally seed
              seed [Name]       Run all seeders, or one by class name
              status            Show migration and seeder status
              drop --force      Drop the entire database
              help              Show this help

        TXT);
        return 0;
    }
}
