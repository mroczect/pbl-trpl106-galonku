<?php
declare(strict_types=1);

use Database\Seeder\Seeder;
use Database\Support\Output;

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $check = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE invoice_no = ?');
        $check->execute(['INV-DEMO-0001']);
        if ((int) $check->fetchColumn() > 0) {
            Output::info('(already seeded)');
            return;
        }

        $pdo->beginTransaction();
        try {
            $customers = $pdo->query('SELECT id FROM customers ORDER BY id ASC LIMIT 2')
                ->fetchAll(PDO::FETCH_COLUMN);
            $users = $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 2')
                ->fetchAll(PDO::FETCH_COLUMN);
            $products = $pdo->query(
                "SELECT id, price FROM products
                 WHERE sku IN ('GLN-AQUA-19L', 'GLN-RO-19L')
                 ORDER BY FIELD(sku, 'GLN-AQUA-19L', 'GLN-RO-19L')
                 LIMIT 2"
            )->fetchAll(PDO::FETCH_ASSOC);

            if (count($customers) < 2 || count($users) < 2 || count($products) < 2) {
                throw new \RuntimeException(
                    'DemoSeeder requires at least 2 customers, 2 users, and 2 demo products (GLN-AQUA-19L, GLN-RO-19L)'
                );
            }

            $stockStmt = $pdo->prepare('SELECT stock FROM products WHERE id IN (?, ?) FOR UPDATE');
            $stockStmt->execute([$products[0]['id'], $products[1]['id']]);
            $stocks = $stockStmt->fetchAll(PDO::FETCH_COLUMN);
            if (count($stocks) < 2 || (int) $stocks[0] < 1 || (int) $stocks[1] < 1) {
                throw new \RuntimeException('DemoSeeder requires positive stock for both demo products');
            }

            $total = (float) $products[0]['price'] + (float) $products[1]['price'];

            $trxStmt = $pdo->prepare(
                "INSERT INTO transactions
                    (invoice_no, customer_id, user_id, type, total_amount, paid_amount, status, notes)
                 VALUES (?, ?, ?, 'sale', ?, ?, 'paid', 'Demo transaction')"
            );
            $trxStmt->execute(['INV-DEMO-0001', $customers[0], $users[0], $total, $total]);
            $trxId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO transaction_items
                    (transaction_id, product_id, qty, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $itemStmt->execute([$trxId, $products[0]['id'], 1, $products[0]['price'], $products[0]['price']]);
            $itemStmt->execute([$trxId, $products[1]['id'], 1, $products[1]['price'], $products[1]['price']]);

            $updateStmt = $pdo->prepare(
                'UPDATE products SET stock = stock - 1 WHERE id IN (?, ?) AND stock >= 1'
            );
            $updateStmt->execute([$products[0]['id'], $products[1]['id']]);
            if ($updateStmt->rowCount() < 2) {
                throw new \RuntimeException('Failed to decrement stock for demo products');
            }

            $schedStmt = $pdo->prepare(
                "INSERT INTO schedules
                    (customer_id, user_id, scheduled_at, status, notes)
                 VALUES (?, ?, ?, 'pending', 'Water gallon delivery')"
            );
            $schedStmt->execute([
                $customers[1], $users[1],
                date('Y-m-d H:i:s', time() + 86400),
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function priority(): int
    {
        return 50;
    }
};
