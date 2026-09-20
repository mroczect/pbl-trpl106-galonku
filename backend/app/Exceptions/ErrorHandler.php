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
            return;
        }
        if ($e instanceof AuthException) {
            Response::error($e->getMessage(), 401);
            return;
        }
        if ($e instanceof NotFoundException) {
            Response::error($e->getMessage(), 404);
            return;
        }
        if ($e instanceof \InvalidArgumentException) {
            Response::error($e->getMessage(), 400);
            return;
        }

        if ($e instanceof \RuntimeException
            && str_starts_with($e->getMessage(), 'Insufficient stock')) {
            $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
            Response::error($debug ? $e->getMessage() : 'Insufficient stock', 422);
            return;
        }

        if ($e instanceof \PDOException) {
            $sqlState   = $e->errorInfo[0] ?? null;
            $driverMsg  = $e->errorInfo[2] ?? '';

            AppLogger::logger()->error('PDOException', [
                'sqlstate'        => $sqlState,
                'driver_message'  => $driverMsg,
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
                'trace'           => $e->getTraceAsString(),
            ]);

            if ($sqlState === '23000') {
                if (str_contains($driverMsg, 'Duplicate entry')) {
                    Response::error('Duplicate entry', 409);
                    return;
                }
                if (str_contains($driverMsg, 'foreign key constraint')) {
                    Response::error('Referenced record not found', 422);
                    return;
                }
                if (str_contains($driverMsg, 'Check constraint')) {
                    Response::error('Value violates constraint', 422);
                    return;
                }
                Response::error('Database constraint violation', 422);
                return;
            }

            Response::error('Database error', 500);
            return;
        }

        AppLogger::logger()->error($e->getMessage(), [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);

        $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        Response::error(
            $debug ? $e->getMessage() : 'Internal server error',
            500
        );
    }
}
