<?php
declare(strict_types=1);

namespace Tests\Support;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Router;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tests\Support\Concerns\ActsAsUser;
use Tests\Support\Concerns\InteractsWithDatabase;
use Tests\Support\Concerns\InteractsWithHttp;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithDatabase;
    use InteractsWithHttp;
    use ActsAsUser;

    protected Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        Response::$testMode = true;
        Auth::reset();

        $this->refreshDatabase();
        $this->bootstrapRouter();
    }

    protected function tearDown(): void
    {
        Response::$testMode = false;
        Auth::reset();

        $this->tearDownDatabase();

        parent::tearDown();
    }

    protected function bootstrapRouter(): void
    {
        $this->router = new Router();
        $router = $this->router;
        require dirname(__DIR__, 2) . '/routes/api.php';
    }
}
