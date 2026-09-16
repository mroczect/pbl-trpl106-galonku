<?php
namespace App\Core;

use App\Exceptions\ErrorHandler;

class App
{
    private Router $router;

    public static function boot(): self
    {
        $app = new self();
        $app->registerErrorHandler();
        $app->registerCors();
        $app->registerSecurityHeaders();

        $app->router = new Router();

        $router = $app->router;
        require dirname(__DIR__, 2) . '/routes/api.php';

        return $app;
    }

    public function run(): void
    {
        try {
            $this->router->dispatch(Request::capture());
        } catch (\Throwable $e) {
            ErrorHandler::handle($e);
        }
    }

    public function router(): Router
    {
        return $this->router;
    }

    private function registerErrorHandler(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? '1' : '0');
        set_exception_handler([ErrorHandler::class, 'handle']);
    }

    private function registerCors(): void
    {
        $cors = require dirname(__DIR__, 2) . '/config/cors.php';

        $origin = '*';
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            if (in_array('*', $cors['allowed_origins'], true) ||
                in_array($_SERVER['HTTP_ORIGIN'], $cors['allowed_origins'], true)) {
                $origin = $_SERVER['HTTP_ORIGIN'];
            }
        }

        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Methods: {$cors['allowed_methods']}");
        header("Access-Control-Allow-Headers: {$cors['allowed_headers']}");
        if ($cors['credentials']) {
            header('Access-Control-Allow-Credentials: true');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    private function registerSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('X-XSS-Protection: 1; mode=block');
    }
}
