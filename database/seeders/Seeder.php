<?php

abstract class Seeder
{
    abstract public function run(PDO $pdo): void;
}
