<?php
namespace App\Services;

use App\Core\Auth;
use App\Models\User;

class AuthService
{
    public static function register(array $data): int
    {
        return User::create([
            'role_id'       => 3,
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'password_hash' => Auth::hash($data['password']),
            'is_active'     => 1,
        ]);
    }
}
