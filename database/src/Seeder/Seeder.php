<?php
declare(strict_types=1);

namespace Database\Seeder;

use PDO;

abstract class Seeder
{
    abstract public function run(PDO $pdo): void;

    public function priority(): int
    {
        return 100;
    }
}
