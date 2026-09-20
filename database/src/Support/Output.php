<?php
declare(strict_types=1);

namespace Database\Support;

final class Output
{
    private const COLOR = [
        'reset'  => "\033[0m",
        'bold'   => "\033[1m",
        'dim'    => "\033[2m",
        'red'    => "\033[31m",
        'green'  => "\033[32m",
        'yellow' => "\033[33m",
        'cyan'   => "\033[36m",
    ];

    private static ?bool $decorated = null;

    public static function color(string $text, string $color): string
    {
        if (!self::decorated()) {
            return $text;
        }
        return (self::COLOR[$color] ?? '') . $text . self::COLOR['reset'];
    }

    public static function info(string $msg): void
    {
        fwrite(STDOUT, $msg . PHP_EOL);
    }

    public static function line(string $msg = ''): void
    {
        fwrite(STDOUT, $msg . PHP_EOL);
    }

    public static function ok(string $msg): void
    {
        fwrite(STDOUT, self::color('✔', 'green') . ' ' . $msg . PHP_EOL);
    }

    public static function warn(string $msg): void
    {
        fwrite(STDERR, self::color('⚠', 'yellow') . ' ' . $msg . PHP_EOL);
    }

    public static function fail(string $msg): void
    {
        fwrite(STDERR, self::color('✘', 'red') . ' ' . $msg . PHP_EOL);
    }

    public static function progress(string $label): void
    {
        fwrite(STDOUT, '  ' . str_pad($label, 58, '.', STR_PAD_RIGHT) . ' ');
    }

    public static function done(): void
    {
        fwrite(STDOUT, self::color('done', 'green') . PHP_EOL);
    }

    public static function skip(string $reason = 'skipped'): void
    {
        fwrite(STDOUT, self::color($reason, 'dim') . PHP_EOL);
    }

    public static function heading(string $msg): void
    {
        fwrite(STDOUT, PHP_EOL . self::color($msg, 'bold') . PHP_EOL);
    }

    private static function decorated(): bool
    {
        if (self::$decorated === null) {
            $isTty = (function_exists('posix_isatty') && posix_isatty(STDOUT))
                || stream_isatty(STDOUT);
            self::$decorated = $isTty && getenv('NO_COLOR') === false;
        }
        return self::$decorated;
    }
}
