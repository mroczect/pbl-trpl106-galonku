<?php
declare(strict_types=1);

namespace Database\Seeder;

use Database\Support\Output;
use PDO;

final class Runner
{
    public function __construct(
        private Repository $repository,
        private PDO $pdo,
    ) {}

    public function run(?string $only = null): int
    {
        $map = $this->repository->all();

        if ($only !== null) {
            if (!isset($map[$only])) {
                throw new \InvalidArgumentException(
                    "Seeder not found: {$only}. Available: "
                    . implode(', ', array_keys($map) ?: ['(none)'])
                );
            }
            $map = [$only => $map[$only]];
        }

        if (empty($map)) {
            Output::info('No seeders found.');
            return 0;
        }

        Output::info('Running ' . count($map) . ' seeder(s)');
        $ran = 0;
        $failed = 0;

        foreach ($map as $name => $file) {
            Output::progress($name);
            $seeder = $this->repository->instance($name);

            if ($seeder === null) {
                Output::skip('not a Seeder');
                $failed++;
                continue;
            }

            try {
                $seeder->run($this->pdo);
                $ran++;
                Output::done();
            } catch (\Throwable $e) {
                Output::fail($e->getMessage());
                $failed++;
            }
        }

        if ($failed > 0) {
            Output::warn("{$failed} seeder(s) failed");
        }

        return $ran;
    }
}
