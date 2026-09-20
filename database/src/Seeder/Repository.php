<?php
declare(strict_types=1);

namespace Database\Seeder;

use Database\Support\Guard;

final class Repository
{
    private string $path;
    private array $cache = [];

    public function __construct(string $seederPath)
    {
        Guard::assertDirExists($seederPath, 'Seeder');
        $this->path = rtrim($seederPath, '/');
    }

    public function all(): array
    {
        $files = glob("{$this->path}/*Seeder.php") ?: [];
        $found = [];

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if ($name === 'Seeder') {
                continue;
            }
            $found[$name] = $file;
        }

        uasort($found, function (string $fa, string $fb): int {
            $pa = $this->priorityFor(basename($fa, '.php'));
            $pb = $this->priorityFor(basename($fb, '.php'));
            return ($pa <=> $pb) ?: strcmp($fa, $fb);
        });

        return $found;
    }

    public function find(string $className): ?string
    {
        return $this->all()[$className] ?? null;
    }

    public function instance(string $name): ?Seeder
    {
        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }
        $file = "{$this->path}/{$name}.php";
        if (!is_file($file)) {
            return $this->cache[$name] = null;
        }
        $instance = require $file;
        if (!$instance instanceof Seeder) {
            return $this->cache[$name] = null;
        }
        return $this->cache[$name] = $instance;
    }

    private function priorityFor(string $name): int
    {
        $instance = $this->instance($name);
        return $instance?->priority() ?? PHP_INT_MAX;
    }
}
