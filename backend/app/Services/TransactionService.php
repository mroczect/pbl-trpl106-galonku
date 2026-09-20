<?php
namespace App\Services;

use App\Core\Database;
use App\Models\{Transaction, Log, StockMovement};
use App\Support\AppLogger;
use App\Exceptions\NotFoundException;

class TransactionService
{
    public static function create(array $data, int $userId): array
    {
        return Database::transaction(function () use ($data, $userId) {
            $db = Database::connect();

            $custStmt = $db->prepare(
                "SELECT id FROM customers WHERE id = ? AND is_active = 1 LIMIT 1"
            );
            $custStmt->execute([(int) $data['customer_id']]);
            if (!$custStmt->fetchColumn()) {
                throw new NotFoundException('Customer not found');
            }

            $aggregated = [];
            foreach ($data['items'] as $item) {
                if (!isset($item['product_id'], $item['qty'])) {
                    throw new \InvalidArgumentException('Each item needs product_id and qty');
                }
                $pid = (int) $item['product_id'];
                $qty = (int) $item['qty'];

                if ($qty < 1) {
                    throw new \InvalidArgumentException('Quantity must be at least 1');
                }

                $aggregated[$pid] = ($aggregated[$pid] ?? 0) + $qty;
            }

            $invoiceNo   = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $totalAmount = 0;
            $itemsData   = [];

            foreach ($aggregated as $productId => $totalQty) {
                $stmt = $db->prepare(
                    "SELECT * FROM products WHERE id = ? AND is_active = 1 FOR UPDATE"
                );
                $stmt->execute([$productId]);
                $product = $stmt->fetch();

                if (!$product) {
                    throw new NotFoundException("Product ID $productId not found");
                }

                if ((int) $product['stock'] < $totalQty) {
                    throw new \RuntimeException("Insufficient stock for {$product['name']}");
                }

                $subtotal = (float) $product['price'] * $totalQty;
                $totalAmount += $subtotal;

                $itemsData[] = [
                    'product_id' => $productId,
                    'qty'        => $totalQty,
                    'unit_price' => (float) $product['price'],
                    'subtotal'   => $subtotal,
                ];
            }

            $paidAmount = (float) ($data['paid_amount'] ?? 0);
            if ($paidAmount > $totalAmount) {
                throw new \InvalidArgumentException('paid_amount cannot exceed total_amount');
            }

            $status = $data['status'] ?? null;
            if (!$status) {
                $status = $paidAmount <= 0 ? 'pending' : ($paidAmount >= $totalAmount ? 'paid' : 'partial');
            }

            $trxId = Transaction::create([
                'invoice_no'   => $invoiceNo,
                'customer_id'  => (int) $data['customer_id'],
                'user_id'      => $userId,
                'type'         => $data['type'] ?? 'sale',
                'total_amount' => $totalAmount,
                'paid_amount'  => $paidAmount,
                'status'       => $status,
                'notes'        => $data['notes'] ?? null,
            ]);

            $stmt = $db->prepare(
                "INSERT INTO transaction_items
                 (transaction_id, product_id, qty, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $updateStock = $db->prepare(
                "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?"
            );

            foreach ($itemsData as $it) {
                $stmt->execute([
                    $trxId,
                    $it['product_id'],
                    $it['qty'],
                    $it['unit_price'],
                    $it['subtotal'],
                ]);

                $lock = $db->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
                $lock->execute([$it['product_id']]);
                $before = (int) $lock->fetchColumn();
                $after  = $before - $it['qty'];

                if ($after < 0) {
                    throw new \RuntimeException("Insufficient stock for product #{$it['product_id']}");
                }

                $updateStock->execute([$it['qty'], $it['product_id'], $it['qty']]);

                StockMovement::create([
                    'product_id'     => $it['product_id'],
                    'user_id'        => $userId,
                    'type'           => 'out',
                    'qty'            => $it['qty'],
                    'stock_before'   => $before,
                    'stock_after'    => $after,
                    'reason'         => 'Penjualan',
                    'reference_type' => 'transaction',
                    'reference_id'   => $trxId,
                ]);
            }

            Log::create([
                'user_id'   => $userId,
                'action'    => 'create',
                'entity'    => 'transaction',
                'entity_id' => $trxId,
                'payload'   => json_encode([
                    'invoice_no' => $invoiceNo,
                    'total'      => $totalAmount,
                    'items'      => count($itemsData),
                ]),
            ]);

            AppLogger::logger()->info("Transaction $invoiceNo created", [
                'total' => $totalAmount,
            ]);

            return [
                'id'           => $trxId,
                'invoice_no'   => $invoiceNo,
                'total_amount' => $totalAmount,
                'items_count'  => count($itemsData),
            ];
        });
    }
}
