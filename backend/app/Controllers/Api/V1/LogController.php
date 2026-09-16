<?php
namespace App\Controllers\Api\V1;

use App\Core\{Request, Response};
use App\Models\Log;

class LogController
{
    public function index(Request $req): void
    {
        $limit = (int) $req->query('limit', 100);
        Response::success(Log::latest($limit));
    }
}
