<?php
namespace App\Controllers;

use App\Core\{Request, Response, Validator, Auth};
use App\Models\User;

class AuthController
{
    public function register(Request $req): void
    {
        Validator::make($req->body, [
            'name'     => 'required|min:3',
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (User::emailExists($req->body['email'])) {
            Response::error('Email sudah terdaftar', 409);
        }

        // Default role: pelanggan (role_id = 3)
        $id = User::create([
            'role_id'       => 3,
            'name'          => $req->body['name'],
            'email'         => $req->body['email'],
            'phone'         => $req->body['phone'] ?? null,
            'password_hash' => Auth::hash($req->body['password']),
            'is_active'     => 1,
        ]);

        log_action($id, 'register', 'user', $id, [
            'name'  => $req->body['name'],
            'email' => $req->body['email'],
        ]);

        Response::success(['id' => $id], 'Registrasi berhasil', 201);
    }

    public function login(Request $req): void
    {
        Validator::make($req->body, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = Auth::attempt($req->body['email'], $req->body['password']);
        if (!$user) {
            Response::error('Email atau password salah', 401);
        }

        // Update last_login_at
        User::updateLastLogin($user['id']);

        $token = generate_jwt([
            'sub'   => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role_name'],
        ]);

        log_action($user['id'], 'login', 'user', $user['id'], [
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
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) ($_ENV['JWT_EXPIRE'] ?? 3600),
        ], 'Login berhasil');
    }

    public function me(Request $req): void
    {
        $user = Auth::currentUser();
        if (!$user) Response::error('Belum login', 401);

        unset($user['password_hash']);
        Response::success($user);
    }

    public function logout(Request $req): void
    {
        $user = Auth::currentUser();
        if ($user) {
            log_action($user['id'], 'logout', 'user', $user['id'], null);
        }

        Response::success(null, 'Logout berhasil');
    }
}
