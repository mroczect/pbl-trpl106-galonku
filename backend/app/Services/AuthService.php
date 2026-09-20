<?php
namespace App\Services;

use App\Core\Auth;
use App\Models\{User, Role};
use App\Support\AppLogger;

class AuthService
{
    public static function register(array $data): int
    {
        $role = Role::findByName('customer');
        if (!$role) {
            throw new \RuntimeException('Default role "customer" not found');
        }
        $roleId = $role['id'];

        $id = User::create([
            'role_id'       => $roleId,
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'password_hash' => Auth::hash($data['password']),
            'is_active'     => 1,
        ]);

        AppLogger::action($id, 'register', 'user', $id, [
            'email' => $data['email'],
        ]);

        return $id;
    }
}
