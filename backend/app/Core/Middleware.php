<?php
namespace App\Core;

abstract class Middleware
{
    abstract public static function handle(Request $req, mixed ...$args): void;
}
