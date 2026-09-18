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

        if ($e instanceof \PDOException) {
            $sqlState = $e->errorInfo[0] ?? null;

            if ($sqlState === '23000') {
                $msg = $e->errorInfo[2] ?? 'Database constraint violation';

                if (str_contains($msg, 'Duplicate entry')) {
                    Response::error('Duplicate entry', 409);
                }
                if (str_contains($msg, 'foreign key constraint')) {
                    Response::error('Referenced record not found', 422);
                }
                if (str_contains($msg, 'Check constraint')) {
                    Response::error('Value violates constraint', 422);
                }
                Response::error('Database constraint violation', 422);
            }
        }

        if ($e instanceof \RuntimeException && str_starts_with($e->getMessage(), 'Insufficient stock')) {
            $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
            Response::error(
                $debug ? $e->getMessage() : 'Insufficient stock',
                422
            );
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
