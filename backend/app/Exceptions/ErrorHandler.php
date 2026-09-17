<?php
namespace App\Exceptions;

use App\Core\Response;
use App\Support\AppLogger;

class ErrorHandler
{
    public static function handle(\Throwable $e): void
    {
        if ($e instanceof ValidationException) {
            Response::error($e->getMessage(), 422, $e->getErrors());
        }

        if ($e instanceof AuthException) {
            Response::error($e->getMessage(), 401);
        }

        if ($e instanceof NotFoundException) {
            Response::error($e->getMessage(), 404);
        }

        if ($e instanceof \InvalidArgumentException) {
            Response::error($e->getMessage(), 400);
        }

        if ($e instanceof \RuntimeException && str_starts_with($e->getMessage(), 'Insufficient stock')) {
            Response::error($e->getMessage(), 422);
        }
        $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';

        AppLogger::logger()->error($e->getMessage(), [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        Response::error(
            $debug ? $e->getMessage() : 'Internal server error',
            500
        );
    }
}
