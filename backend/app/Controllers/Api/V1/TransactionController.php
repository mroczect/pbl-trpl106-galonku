<?php
namespace App\Controllers\Api\V1;

use App\Core\{Request, Response, Auth};
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\AppLogger;
use App\Exceptions\NotFoundException;

class TransactionController
{
    public function index(Request $req): void
    {
        $page    = (int) $req->query('page', 1);
        $perPage = (int) $req->query('per_page', 15);

        $filters = [];
        foreach (['status', 'customer_id', 'user_id', 'type'] as $f) {
            if ($req->query($f)) $filters[$f] = $req->query($f);
        }

        $result = Transaction::paginateWithRelations($page, $perPage, $filters);

        Response::success($result['data'], 'OK', 200, [
            'page'        => $result['page'],
            'per_page'    => $result['per_page'],
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
        ]);
    }

    public function show(Request $req, int $id): void
    {
        $trx = Transaction::findWithItems($id);
        if (!$trx) throw new NotFoundException('Transaksi tidak ditemukan');
        Response::success($trx);
    }

    public function store(Request $req): void
    {
        $data = $req->validate([
            'customer_id' => 'required|integer',
            'items'       => 'required|array',
        ]);

        if (empty($data['items'])) {
            Response::error('Items tidak boleh kosong');
        }

        $result = TransactionService::create($req->body(), Auth::id());

        Response::success($result, 'Transaksi berhasil dibuat', 201);
    }

    public function updateStatus(Request $req, int $id): void
    {
        $trx = Transaction::find($id);
        if (!$trx) throw new NotFoundException('Transaksi tidak ditemukan');

        $data = $req->validate([
            'status' => 'required|in:pending,paid,partial,cancelled',
        ]);

        $update = ['status' => $data['status']];
        if ($req->body('paid_amount') !== null) {
            $update['paid_amount'] = (float) $req->body('paid_amount');
        }

        Transaction::update($id, $update);
        AppLogger::action(Auth::id(), 'update', 'transaction', $id, $update);

        Response::success(null, 'Status transaksi diperbarui');
    }
}
