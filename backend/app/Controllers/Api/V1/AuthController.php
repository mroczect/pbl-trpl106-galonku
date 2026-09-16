<?php
namespace App\Controllers\Api\V1;

use App\Core\{Request, Response, Auth};
use App\Models\User;
use App\Services\AuthService;
use App\Support\{AppLogger, Jwt};

class AuthController
{
    public function register(Request $req): void
    {
        $data = $req->validate([
            'name'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:150',
            'password' => 'required|min:6|max:100',
            'phone'    => 'phone',
        ]);

        if (User::emailExists($data['email'])) {
            Response::error('Email sudah terdaftar', 409);
        }

        $id = AuthService::register($data);

        Response::success(['id' => $id], 'Registrasi berhasil', 201);
    }

    public function login(Request $req): void
    {
        $data = $req->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = Auth::attempt($data['email'], $data['password']);
        if (!$user) {
            AppLogger::logger()->warning('Login gagal', ['email' => $data['email']]);
            Response::error('Email atau password salah', 401);
        }

        User::updateLastLogin((int) $user['id']);

        $tokens = Auth::login($user);

        AppLogger::action($user['id'], 'login', 'user', $user['id'], [
            'email' => $user['email'],
        ]);

        Response::success([
            'user' => [
                'id'        => $user['id'],
                'name'      => $user['name'],
                'email'     => $user['email'],
                'phone'     => $user['phone'],
                'role_id'   => $user['role_id'],
                'role_name' => $user['role_name'],
            ],
        ] + $tokens, 'Login berhasil');
    }

    public function me(Request $req): void
    {
        $user = Auth::currentUser();
        if (!$user) Response::error('Belum login', 401);

        unset($user['password_hash']);
        Response::success($user);
    }

    public function refresh(Request $req): void
    {
        $data = $req->validate(['refresh_token' => 'required']);

        $payload = Jwt::verifyRefresh($data['refresh_token']);
        if (!$payload || !isset($payload['sub'])) {
            Response::error('Refresh token tidak valid', 401);
        }

        $user = User::find((int) $payload['sub']);
        if (!$user || (int) $user['is_active'] !== 1) {
            Response::error('User tidak aktif', 401);
        }

        Response::success(Auth::login($user), 'Token diperbarui');
    }

    public function logout(Request $req): void
    {
        $user = Auth::currentUser();
        $token = $req->bearerToken();

        if ($token) Auth::logout($token);
        if ($user) AppLogger::action($user['id'], 'logout', 'user', $user['id'], null);

        Response::success(null, 'Logout berhasil');
    }
}
