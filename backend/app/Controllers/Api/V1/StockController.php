<?php
namespace App\Controllers\Api\V1;

use App\Core\{Request, Response, Auth};
use App\Models\{StockMovement, Product};
use App\Services\StockService;
use App\Support\AppLogger;
use App\Exceptions\NotFoundException;

class StockController
{
    public function index(Request $req): void
    {
        $page    = (int) $req->query('page', 1);
        $perPage = (int) $req->query('per_page', 30);

        $filters = [];
        foreach (['product_id', 'type', 'user_id', 'from', 'to'] as $f) {
            $v = $req->query($f);
            if ($v !== null && $v !== '') $filters[$f] = $v;
        }

        $result = StockMovement::paginate($page, $perPage, $filters);

        Response::success($result['data'], 'OK', 200, [
            'page'        => $result['page'],
            'per_page'    => $result['per_page'],
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
        ]);
    }

    public function adjust(Request $req): void
    {
        $data = $req->validate([
            'product_id' => 'required|integer',
            'new_stock'  => 'required|integer|min:0',
            'reason'     => 'required|min:3|max:100',
            'notes'      => 'max:500',
        ]);

        $product = Product::find((int) $data['product_id']);
        if (!$product) throw new NotFoundException('Product not found');

        $result = StockService::manualAdjust(
            (int) $data['product_id'],
            (int) $data['new_stock'],
            (string) $data['reason'],
            (int) Auth::id(),
            $req->body('notes')
        );

        if (!empty($result['changed'])) {
            AppLogger::action(Auth::id(), 'update', 'stock', (int) $data['product_id'], [
                'reason' => $data['reason'],
                'before' => $result['before'],
                'after'  => $result['after'],
            ]);
        }

        Response::success($result, 'Stock adjusted');
    }
}
