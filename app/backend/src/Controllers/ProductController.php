<?php
namespace App\Controllers;

use App\Core\{Request, Response, Validator, Auth};
use App\Models\Product;

class ProductController
{
    public function index(Request $req): void
    {
        $category = $req->query['category'] ?? null;

        if ($category) {
            $stmt = \App\Core\Database::connect()->prepare(
                "SELECT * FROM products WHERE category = ? AND is_active = 1 ORDER BY id DESC"
            );
            $stmt->execute([$category]);
            Response::success($stmt->fetchAll());
            return;
        }

        Response::success(Product::all());
    }

    public function show(Request $req, int $id): void
    {
        $product = Product::find($id);
        if (!$product) Response::error('Produk tidak ditemukan', 404);
        Response::success($product);
    }

    public function lowStock(Request $req): void
    {
        $threshold = (int) ($req->query['threshold'] ?? 10);
        $stmt = \App\Core\Database::connect()->prepare(
            "SELECT * FROM products WHERE stock <= ? AND is_active = 1 ORDER BY stock ASC"
        );
        $stmt->execute([$threshold]);
        Response::success($stmt->fetchAll());
    }

    public function store(Request $req): void
    {
        Validator::make($req->body, [
            'sku'   => 'required|min:3',
            'name'  => 'required|min:3',
            'price' => 'required|numeric',
        ]);

        // Cek SKU unik
        $exists = \App\Core\Database::connect()->prepare(
            "SELECT 1 FROM products WHERE sku = ? LIMIT 1"
        );
        $exists->execute([$req->body['sku']]);
        if ($exists->fetchColumn()) {
            Response::error('SKU sudah digunakan', 409);
        }

        $id = Product::create([
            'sku'      => $req->body['sku'],
            'name'     => $req->body['name'],
            'category' => $req->body['category'] ?? 'galon',
            'price'    => (float) $req->body['price'],
            'stock'    => (int) ($req->body['stock'] ?? 0),
            'is_active'=> 1,
        ]);

        log_action(Auth::id(), 'create', 'product', $id, ['sku' => $req->body['sku']]);

        Response::success(['id' => $id], 'Produk ditambahkan', 201);
    }

    public function update(Request $req, int $id): void
    {
        if (!Product::find($id)) Response::error('Produk tidak ditemukan', 404);

        $data = [];
        foreach (['name', 'category'] as $f) {
            if (isset($req->body[$f])) $data[$f] = $req->body[$f];
        }
        if (isset($req->body['price'])) $data['price'] = (float) $req->body['price'];
        if (isset($req->body['stock'])) $data['stock'] = (int) $req->body['stock'];
        if (isset($req->body['is_active'])) $data['is_active'] = (int) $req->body['is_active'];

        if (empty($data)) Response::error('Tidak ada data yang diubah');

        Product::update($id, $data);
        log_action(Auth::id(), 'update', 'product', $id, $data);

        Response::success(null, 'Produk diperbarui');
    }

    public function destroy(Request $req, int $id): void
    {
        if (!Product::find($id)) Response::error('Produk tidak ditemukan', 404);

        Product::update($id, ['is_active' => 0]);
        log_action(Auth::id(), 'delete', 'product', $id, null);

        Response::success(null, 'Produk dinonaktifkan');
    }
}
