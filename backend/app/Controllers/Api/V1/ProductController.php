<?php
namespace App\Controllers\Api\V1;

use App\Core\{Request, Response, Auth};
use App\Models\Product;
use App\Support\AppLogger;
use App\Exceptions\NotFoundException;

class ProductController
{
    public function index(Request $req): void
    {
        $page     = (int) $req->query('page', 1);
        $perPage  = (int) $req->query('per_page', 15);
        $category = $req->query('category');

        $filters = [];
        if ($category) $filters['category'] = $category;

        $result = Product::paginate($page, $perPage, $filters);

        Response::success($result['data'], 'OK', 200, [
            'page'        => $result['page'],
            'per_page'    => $result['per_page'],
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
        ]);
    }

    public function show(Request $req, int $id): void
    {
        $product = Product::find($id);
        if (!$product) throw new NotFoundException('Produk tidak ditemukan');
        Response::success($product);
    }

    public function lowStock(Request $req): void
    {
        $threshold = (int) $req->query('threshold', 10);
        Response::success(Product::lowStock($threshold));
    }

    public function store(Request $req): void
    {
        $data = $req->validate([
            'sku'      => 'required|min:3|max:50',
            'name'     => 'required|min:3|max:150',
            'price'    => 'required|numeric',
            'category' => 'in:galon,air,aksesoris,lain',
            'stock'    => 'integer',
        ]);

        if (Product::skuExists($data['sku'])) {
            Response::error('SKU sudah digunakan', 409);
        }

        $id = Product::create([
            'sku'       => $data['sku'],
            'name'      => $data['name'],
            'category'  => $data['category'] ?? 'galon',
            'price'     => (float) $data['price'],
            'stock'     => (int) ($data['stock'] ?? 0),
            'is_active' => 1,
        ]);

        AppLogger::action(Auth::id(), 'create', 'product', $id, ['sku' => $data['sku']]);

        Response::success(['id' => $id], 'Produk ditambahkan', 201);
    }

    public function update(Request $req, int $id): void
    {
        if (!Product::find($id)) throw new NotFoundException('Produk tidak ditemukan');

        $data = $req->validate([
            'name'      => 'min:3|max:150',
            'category'  => 'in:galon,air,aksesoris,lain',
            'price'     => 'numeric',
            'stock'     => 'integer',
            'is_active' => 'boolean',
        ]);

        if (empty($data)) Response::error('Tidak ada data yang diubah');

        if (isset($data['price'])) $data['price'] = (float) $data['price'];
        if (isset($data['stock'])) $data['stock'] = (int) $data['stock'];
        if (isset($data['is_active'])) $data['is_active'] = (int) $data['is_active'];

        Product::update($id, $data);
        AppLogger::action(Auth::id(), 'update', 'product', $id, $data);

        Response::success(null, 'Produk diperbarui');
    }

    public function destroy(Request $req, int $id): void
    {
        if (!Product::find($id)) throw new NotFoundException('Produk tidak ditemukan');

        Product::update($id, ['is_active' => 0]);
        AppLogger::action(Auth::id(), 'delete', 'product', $id, null);

        Response::success(null, 'Produk dinonaktifkan');
    }
}
