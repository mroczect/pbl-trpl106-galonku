<?php
namespace App\Controllers\Api\V1;

use App\Core\{Request, Response};
use App\Models\Role;

class RoleController
{
    public function index(Request $req): void
    {
        Response::success(Role::all());
    }
}
