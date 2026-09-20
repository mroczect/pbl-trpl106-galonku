#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Database\Cli;
use Database\Migrator;

$migrator = new Migrator(__DIR__ . '/migrations', __DIR__ . '/seeders');
exit((new Cli($migrator))->run($argv));