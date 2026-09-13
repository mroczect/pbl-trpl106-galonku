<?php
namespace App\Controllers;

use App\Core\{Request, Response};
use App\Models\Role;

class RoleController
{
    public function index(Request $req): void
    {
        Response::success(Role::all());
    }
}
