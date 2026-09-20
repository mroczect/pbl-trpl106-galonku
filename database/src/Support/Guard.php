<?php
declare(strict_types=1);

namespace Database\Support;

final class Guard
{
    public static function isValidIdentifier(string $name): bool
    {
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name);
    }

    public static function assertSafeIdentifier(string $name): void
    {
        if (!self::isValidIdentifier($name)) {
            throw new \InvalidArgumentException("Unsafe identifier: {$name}");
        }
    }

    public static function quoteIdentifier(string $name): string
    {
        self::assertSafeIdentifier($name);
        return '`' . $name . '`';
    }

    public static function assertSafeDatabaseName(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException("Unsafe database name: {$name}");
        }
    }

    public static function assertFileExists(string $path, string $label): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException("{$label} not found: {$path}");
        }
    }

    public static function assertDirExists(string $path, string $label): void
    {
        if (!is_dir($path)) {
            throw new \RuntimeException("{$label} directory not found: {$path}");
        }
    }
}