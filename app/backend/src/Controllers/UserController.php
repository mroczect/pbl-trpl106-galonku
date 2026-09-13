<?php
namespace App\Controllers;

use App\Core\{Request, Response, Auth};
use App\Models\User;

class UserController
{
    public function index(Request $req): void
    {
        $stmt = \App\Core\Database::connect()->query(
            "SELECT u.id, u.role_id, u.name, u.email, u.phone, u.is_active,
                    u.last_login_at, u.created_at, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             ORDER BY u.id DESC"
        );
        Response::success($stmt->fetchAll());
    }

    public function show(Request $req, int $id): void
    {
        $user = User::find($id);
        if (!$user) Response::error('User tidak ditemukan', 404);
        unset($user['password_hash']);
        Response::success($user);
    }

    public function update(Request $req, int $id): void
    {
        if (!User::find($id)) Response::error('User tidak ditemukan', 404);

        $data = [];
        if (isset($req->body['name']))  $data['name']  = $req->body['name'];
        if (isset($req->body['phone'])) $data['phone'] = $req->body['phone'];
        if (isset($req->body['role_id'])) $data['role_id'] = (int) $req->body['role_id'];
        if (isset($req->body['is_active'])) $data['is_active'] = (int) $req->body['is_active'];

        if (empty($data)) Response::error('Tidak ada data yang diubah');

        User::update($id, $data);
        log_action(Auth::id(), 'update', 'user', $id, $data);

        Response::success(null, 'User diperbarui');
    }

    public function destroy(Request $req, int $id): void
    {
        if (!User::find($id)) Response::error('User tidak ditemukan', 404);

        User::update($id, ['is_active' => 0]);
        log_action(Auth::id(), 'delete', 'user', $id, null);

        Response::success(null, 'User dinonaktifkan');
    }
}
