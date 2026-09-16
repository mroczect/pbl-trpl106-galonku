<?php
require_once __DIR__ . '/Seeder.php';

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
        if ($count > 0) {
            echo "(already seeded) ";
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO transactions
                (invoice_no, customer_id, user_id, type, total_amount, paid_amount, status, notes)
            VALUES (?, ?, ?, 'sale', ?, ?, 'paid', 'Transaksi demo')
        ");
        $stmt->execute(['INV-DEMO-0001', 1, 1, 26000.00, 26000.00]);
        $trxId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO transaction_items
                (transaction_id, product_id, qty, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$trxId, 1, 1, 20000.00, 20000.00]);
        $stmt->execute([$trxId, 2, 1, 6000.00,  6000.00]);

        $pdo->exec("UPDATE products SET stock = stock - 1 WHERE id IN (1, 2)");

        $stmt = $pdo->prepare("
            INSERT INTO schedules
                (customer_id, user_id, scheduled_at, status, notes)
            VALUES (?, ?, ?, 'pending', 'Antar galon')
        ");
        $stmt->execute([2, 2, date('Y-m-d H:i:s', time() + 86400)]);
    }
};
