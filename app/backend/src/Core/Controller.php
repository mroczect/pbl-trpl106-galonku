<?php
namespace App\Core;

abstract class Controller
{
    protected function validate(array $data, array $rules): array
    {
        return Validator::make($data, $rules);
    }

    protected function currentUser(): ?array
    {
        return Auth::currentUser();
    }

    protected function requireAuth(): void
    {
        if (!Auth::currentUser()) {
            Response::error('Belum login', 401);
        }
    }

    protected function requireRole(string ...$roles): void
    {
        $this->requireAuth();
        if (!in_array(Auth::roleName(), $roles, true)) {
            Response::error('Akses ditolak', 403);
        }
    }
}
