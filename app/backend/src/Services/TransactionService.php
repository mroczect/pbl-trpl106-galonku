<?php
namespace App\Services;

use App\Core\Database;
use App\Models\{Product, Transaction, Log};

class TransactionService
{
    public static function create(array $data, int $userId): array
    {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            // Generate invoice: INV-YYYYMMDD-XXXX
            $invoiceNo = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            // Hitung total dulu dari item
            $totalAmount = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $product = Product::find((int) $item['product_id']);
                if (!$product) {
                    throw new \Exception("Produk ID {$item['product_id']} tidak ditemukan");
                }
                $qty = (int) $item['qty'];
                if ($qty < 1) throw new \Exception("Qty harus >= 1");

                if ((int) $product['stock'] < $qty) {
                    throw new \Exception("Stok {$product['name']} tidak cukup");
                }

                $subtotal = $product['price'] * $qty;
                $totalAmount += $subtotal;

                $itemsData[] = [
                    'product_id' => $product['id'],
                    'qty'        => $qty,
                    'unit_price' => $product['price'],
                    'subtotal'   => $subtotal,
                    'name'       => $product['name'],
                ];
            }

            // Insert transaksi
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

            // Insert items + kurangi stok
            $stmt = $db->prepare(
                "INSERT INTO transaction_items (transaction_id, product_id, qty, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );

            foreach ($itemsData as $it) {
                $stmt->execute([$trxId, $it['product_id'], $it['qty'], $it['unit_price'], $it['subtotal']]);

                // Kurangi stok
                if (!Product::reduceStock($it['product_id'], $it['qty'])) {
                    throw new \Exception("Gagal kurangi stok {$it['name']}");
                }
            }

            // Log
            Log::create([
                'user_id'   => $userId,
                'action'    => 'create',
                'entity'    => 'transaction',
                'entity_id' => $trxId,
                'payload'   => [
                    'invoice_no' => $invoiceNo,
                    'total'      => $totalAmount,
                    'items'      => count($itemsData),
                ],
            ]);

            $db->commit();

            return [
                'id'          => $trxId,
                'invoice_no'  => $invoiceNo,
                'total_amount'=> $totalAmount,
                'items_count' => count($itemsData),
            ];
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
