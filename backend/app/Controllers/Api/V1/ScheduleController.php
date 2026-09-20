<?php
namespace App\Controllers\Api\V1;

use App\Core\{Request, Response, Auth};
use App\Models\Schedule;
use App\Support\AppLogger;
use App\Exceptions\NotFoundException;

class ScheduleController
{
    public function index(Request $req): void
    {
        $page    = (int) $req->query('page', 1);
        $perPage = (int) $req->query('per_page', 15);

        $filters = [];
        foreach (['status', 'user_id', 'customer_id'] as $f) {
            if ($req->query($f)) $filters[$f] = $req->query($f);
        }

        $result = Schedule::paginateWithRelations($page, $perPage, $filters);

        Response::success($result['data'], 'OK', 200, [
            'page'        => $result['page'],
            'per_page'    => $result['per_page'],
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
        ]);
    }

    public function show(Request $req, int $id): void
    {
        $s = Schedule::find($id);
        if (!$s) throw new NotFoundException('Schedule not found');
        Response::success($s);
    }

    public function store(Request $req): void
    {
        $data = $req->validate([
            'customer_id'  => 'required|integer',
            'user_id'      => 'required|integer',
            'scheduled_at' => 'required|date',
        ]);

        if (!preg_match(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) $data['scheduled_at']
        )) {
            Response::error('scheduled_at must be in Y-m-d H:i:s format', 422);
            return;
        }

        $customer = \App\Models\Customer::find((int) $data['customer_id']);
        if (!$customer || (int) $customer['is_active'] !== 1) {
            throw new NotFoundException('Customer not found or inactive');
        }

        $user = \App\Models\User::find((int) $data['user_id']);
        if (!$user || (int) $user['is_active'] !== 1) {
            throw new NotFoundException('User not found or inactive');
        }

        $when = strtotime($data['scheduled_at']);
        if ($when === false || $when <= time()) {
            Response::error('scheduled_at must be in the future', 422);
            return;
        }

        $id = Schedule::create([
            'customer_id'  => (int) $data['customer_id'],
            'user_id'      => (int) $data['user_id'],
            'scheduled_at' => $data['scheduled_at'],
            'status'       => 'pending',
            'notes'        => $req->body('notes'),
        ]);

        AppLogger::action(Auth::id(), 'create', 'schedule', $id, null);

        Response::success(['id' => $id], 'Schedule created', 201);
    }

    public function updateStatus(Request $req, int $id): void
    {
        if (!Schedule::find($id)) throw new NotFoundException('Schedule not found');

        $data = $req->validate([
            'status' => 'required|in:pending,on_route,done,cancelled',
        ]);

        Schedule::update($id, ['status' => $data['status']]);
        AppLogger::action(Auth::id(), 'update', 'schedule', $id, ['status' => $data['status']]);

        Response::success(null, 'Schedule status updated');
    }
}
