<?php
namespace App\Middleware;

use App\Core\{Auth, Request, Response};

class AuthMiddleware
{
    public static function handle(Request $req, mixed ...$args): void
    {
        if (!Auth::currentUser()) {
            Response::error('Invalid token or not authenticated', 401);
        }
    }
}
