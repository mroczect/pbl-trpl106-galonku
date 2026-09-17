<?php
namespace App\Controllers\Api\V1;

use App\Core\{Request, Response, Auth};
use App\Models\User;
use App\Support\AppLogger;
use App\Exceptions\NotFoundException;

class UserController
{
    public function index(Request $req): void
    {
        $page    = (int) $req->query('page', 1);
        $perPage = (int) $req->query('per_page', 15);

        $result = User::paginateWithRole($page, $perPage);

        Response::success($result['data'], 'OK', 200, [
            'page'        => $result['page'],
            'per_page'    => $result['per_page'],
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
        ]);
    }

    public function show(Request $req, int $id): void
    {
        $user = User::find($id);
        if (!$user) throw new NotFoundException('User not found');
        unset($user['password_hash']);
        Response::success($user);
    }

    public function update(Request $req, int $id): void
    {
        if (!User::find($id)) throw new NotFoundException('User not found');

        $data = $req->validate([
            'name'      => 'min:3|max:100',
            'phone'     => 'phone',
            'role_id'   => 'integer',
            'is_active' => 'boolean',
        ]);

        if (empty($data)) Response::error('No data to update');

        User::update($id, $data);
        AppLogger::action(Auth::id(), 'update', 'user', $id, $data);

        Response::success(null, 'User updated');
    }

    public function destroy(Request $req, int $id): void
    {
        if (!User::find($id)) throw new NotFoundException('User not found');

        User::update($id, ['is_active' => 0]);
        AppLogger::action(Auth::id(), 'delete', 'user', $id, null);

        Response::success(null, 'User deactivated');
    }
}
