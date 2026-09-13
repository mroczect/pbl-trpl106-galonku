<?php
namespace App\Controllers;

use App\Core\{Request, Response, Validator, Auth};
use App\Services\TransactionService;

class TransactionController
{
    public function index(Request $req): void
    {
        $stmt = \App\Core\Database::connect()->query(
            "SELECT t.*, c.name AS customer_name, u.name AS user_name
             FROM transactions t
             JOIN customers c ON c.id = t.customer_id
             JOIN users u ON u.id = t.user_id
             ORDER BY t.id DESC"
        );
        Response::success($stmt->fetchAll());
    }

    public function show(Request $req, int $id): void
    {
        $db = \App\Core\Database::connect();

        $stmt = $db->prepare(
            "SELECT t.*, c.name AS customer_name, u.name AS user_name
             FROM transactions t
             JOIN customers c ON c.id = t.customer_id
             JOIN users u ON u.id = t.user_id
             WHERE t.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $trx = $stmt->fetch();
        if (!$trx) Response::error('Transaksi tidak ditemukan', 404);

        $items = $db->prepare(
            "SELECT ti.*, p.name AS product_name, p.sku
             FROM transaction_items ti
             JOIN products p ON p.id = ti.product_id
             WHERE ti.transaction_id = ?"
        );
        $items->execute([$id]);
        $trx['items'] = $items->fetchAll();

        Response::success($trx);
    }

    public function store(Request $req): void
    {
        Validator::make($req->body, [
            'customer_id' => 'required|numeric',
            'items'       => 'required',
        ]);

        if (!is_array($req->body['items']) || empty($req->body['items'])) {
            Response::error('Items harus berupa array dan tidak kosong');
        }

        $result = TransactionService::create($req->body, Auth::id());
        Response::success($result, 'Transaksi berhasil dibuat', 201);
    }

    public function updateStatus(Request $req, int $id): void
    {
        $trx = \App\Models\Transaction::find($id);
        if (!$trx) Response::error('Transaksi tidak ditemukan', 404);

        Validator::make($req->body, [
            'status' => 'required|in:pending,paid,partial,cancelled',
        ]);

        \App\Models\Transaction::update($id, [
            'status' => $req->body['status'],
            'paid_amount' => isset($req->body['paid_amount']) ? (float) $req->body['paid_amount'] : $trx['paid_amount'],
        ]);

        log_action(Auth::id(), 'update', 'transaction', $id, ['status' => $req->body['status']]);

        Response::success(null, 'Status transaksi diperbarui');
    }
}
