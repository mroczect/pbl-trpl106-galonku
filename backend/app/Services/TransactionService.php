<?php
namespace App\Services;

use App\Core\Database;
use App\Models\{Transaction, Log};
use App\Support\AppLogger;
use App\Exceptions\NotFoundException;

class TransactionService
{
    public static function create(array $data, int $userId): array
    {
        return Database::transaction(function () use ($data, $userId) {
            $db = Database::connect();

            $invoiceNo = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $totalAmount = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $stmt = $db->prepare(
                    "SELECT * FROM products WHERE id = ? AND is_active = 1 FOR UPDATE"
                );
                $stmt->execute([(int) $item['product_id']]);
                $product = $stmt->fetch();

                if (!$product) {
                    throw new NotFoundException("Produk ID {$item['product_id']} tidak ditemukan");
                }

                $qty = (int) $item['qty'];
                if ($qty < 1) throw new \InvalidArgumentException('Qty minimal 1');

                if ((int) $product['stock'] < $qty) {
                    throw new \RuntimeException("Stok {$product['name']} tidak cukup");
                }

                $subtotal = (float) $product['price'] * $qty;
                $totalAmount += $subtotal;

                $itemsData[] = [
                    'product_id' => (int) $product['id'],
                    'qty'        => $qty,
                    'unit_price' => (float) $product['price'],
                    'subtotal'   => $subtotal,
                    'name'       => $product['name'],
                ];
            }

            $trxId = Transaction::create([
                'invoice_no'   => $invoiceNo,
                'customer_id'  => (int) $data['customer_id'],
                'user_id'      => $userId,
                'type'         => $data['type'] ?? 'sale',
                'total_amount' => $totalAmount,
                'paid_amount'  => (float) ($data['paid_amount'] ?? 0),
                'status'       => $data['status'] ?? 'pending',
                'notes'        => $data['notes'] ?? null,
            ]);

            $stmt = $db->prepare(
                "INSERT INTO transaction_items
                 (transaction_id, product_id, qty, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );

            foreach ($itemsData as $it) {
                $stmt->execute([
                    $trxId,
                    $it['product_id'],
                    $it['qty'],
                    $it['unit_price'],
                    $it['subtotal'],
                ]);

                $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                   ->execute([$it['qty'], $it['product_id']]);
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

            AppLogger::logger()->info("Transaksi $invoiceNo dibuat", ['total' => $totalAmount]);

            return [
                'id'           => $trxId,
                'invoice_no'   => $invoiceNo,
                'total_amount' => $totalAmount,
                'items_count'  => count($itemsData),
            ];
        });
    }
}
