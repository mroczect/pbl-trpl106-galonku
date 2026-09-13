<?php
namespace App\Middleware;

use App\Core\{Auth, Request, Response};

class RoleMiddleware
{
    public static function handle(Request $req): void
    {
        if (!Auth::currentUser()) {
            Response::error('Belum login', 401);
        }
    }

    public static function role(Request $req, string ...$roles): void
    {
        $user = Auth::currentUser();
        if (!$user) Response::error('Belum login', 401);

        if (!in_array($user['role_name'], $roles, true)) {
            Response::error('Akses ditolak', 403);
        }
    }
}
