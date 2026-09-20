<?php
namespace App\Services;

use App\Core\Database;
use App\Models\{Product, StockMovement};

class StockService
{
    public static function adjust(
        int $productId,
        int $delta,
        string $type,
        string $reason,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): array {
        $db = Database::connect();

        $stmt = $db->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$productId]);
        $before = $stmt->fetchColumn();
        if ($before === false) {
            throw new \App\Exceptions\NotFoundException("Product #$productId not found");
        }
        $before = (int) $before;
        $after  = $before + $delta;

        if ($after < 0) {
            throw new \RuntimeException('Stock would become negative');
        }

        $upd = $db->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $upd->execute([$after, $productId]);

        StockMovement::create([
            'product_id'     => $productId,
            'user_id'        => $userId,
            'type'           => $type,
            'qty'            => abs($delta),
            'stock_before'   => $before,
            'stock_after'    => $after,
            'reason'         => $reason,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'notes'          => $notes,
        ]);

        return ['before' => $before, 'after' => $after];
    }

    public static function log(
        int $productId,
        int $qty,
        string $type,
        string $reason,
        int $stockBefore,
        int $stockAfter,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): int {
        return StockMovement::create([
            'product_id'     => $productId,
            'user_id'        => $userId,
            'type'           => $type,
            'qty'            => $qty,
            'stock_before'   => $stockBefore,
            'stock_after'    => $stockAfter,
            'reason'         => $reason,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'notes'          => $notes,
        ]);
    }

    public static function manualAdjust(
        int $productId,
        int $newStock,
        string $reason,
        int $userId,
        ?string $notes = null
    ): array {
        return Database::transaction(function () use ($productId, $newStock, $reason, $userId, $notes) {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$productId]);
            $before = (int) $stmt->fetchColumn();
            $after  = $newStock;
            $delta  = $after - $before;

            if ($delta === 0) {
                return ['before' => $before, 'after' => $after, 'changed' => false];
            }

            $upd = $db->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $upd->execute([$after, $productId]);

            StockMovement::create([
                'product_id'   => $productId,
                'user_id'      => $userId,
                'type'         => 'adjustment',
                'qty'          => abs($delta),
                'stock_before' => $before,
                'stock_after'  => $after,
                'reason'       => $reason,
                'notes'        => $notes,
            ]);

            return ['before' => $before, 'after' => $after, 'changed' => true];
        });
    }
}
