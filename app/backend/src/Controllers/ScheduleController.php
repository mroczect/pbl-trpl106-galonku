<?php
namespace App\Controllers;

use App\Core\{Request, Response, Validator, Auth};
use App\Models\Schedule;

class ScheduleController
{
    public function index(Request $req): void
    {
        $stmt = \App\Core\Database::connect()->query(
            "SELECT s.*, c.name AS customer_name, u.name AS user_name
             FROM schedules s
             JOIN customers c ON c.id = s.customer_id
             JOIN users u ON u.id = s.user_id
             ORDER BY s.scheduled_at DESC"
        );
        Response::success($stmt->fetchAll());
    }

    public function show(Request $req, int $id): void
    {
        $s = Schedule::find($id);
        if (!$s) Response::error('Jadwal tidak ditemukan', 404);
        Response::success($s);
    }

    public function store(Request $req): void
    {
        Validator::make($req->body, [
            'customer_id'   => 'required|numeric',
            'user_id'       => 'required|numeric',
            'scheduled_at'  => 'required',
        ]);

        $id = Schedule::create([
            'customer_id'  => (int) $req->body['customer_id'],
            'user_id'      => (int) $req->body['user_id'],
            'scheduled_at' => $req->body['scheduled_at'],
            'status'       => 'pending',
            'notes'        => $req->body['notes'] ?? null,
        ]);

        log_action(Auth::id(), 'create', 'schedule', $id, null);

        Response::success(['id' => $id], 'Jadwal dibuat', 201);
    }

    public function updateStatus(Request $req, int $id): void
    {
        if (!Schedule::find($id)) Response::error('Jadwal tidak ditemukan', 404);

        Validator::make($req->body, [
            'status' => 'required|in:pending,on_route,done,cancelled',
        ]);

        Schedule::update($id, ['status' => $req->body['status']]);
        log_action(Auth::id(), 'update', 'schedule', $id, ['status' => $req->body['status']]);

        Response::success(null, 'Status jadwal diperbarui');
    }
}
