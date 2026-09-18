<?php
namespace App\Controllers\Api\V1;

use App\Core\{Request, Response, Auth};
use App\Models\Customer;
use App\Support\AppLogger;
use App\Exceptions\NotFoundException;

class CustomerController
{
    public function index(Request $req): void
    {
        $page    = (int) $req->query('page', 1);
        $perPage = (int) $req->query('per_page', 15);

        $result = Customer::paginate($page, $perPage, [], 'id DESC');

        Response::success($result['data'], 'OK', 200, [
            'page'        => $result['page'],
            'per_page'    => $result['per_page'],
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
        ]);
    }

    public function show(Request $req, int $id): void
    {
        $c = Customer::find($id);
        if (!$c) throw new NotFoundException('Customer not found');
        Response::success($c);
    }

    public function store(Request $req): void
    {
        $data = $req->validate([
            'name'  => 'required|min:3|max:100',
            'phone' => 'required|phone',
        ]);

        if (Customer::phoneExists($data['phone'])) {
            Response::error('Phone number already registered', 409);
        }

        $id = Customer::create([
            'name'      => $data['name'],
            'phone'     => $data['phone'],
            'address'   => $req->body('address'),
            'notes'     => $req->body('notes'),
            'is_active' => 1,
        ]);

        AppLogger::action(Auth::id(), 'create', 'customer', $id, null);

        Response::success(['id' => $id], 'Customer created', 201);
    }

    public function update(Request $req, int $id): void
    {
        if (!Customer::find($id)) throw new NotFoundException('Customer not found');

        $data = $req->validate([
            'name'      => 'min:3|max:100',
            'phone'     => 'phone',
            'is_active' => 'boolean',
        ]);

        if ($req->body('address') !== null) $data['address'] = $req->body('address');
        if ($req->body('notes') !== null)   $data['notes']   = $req->body('notes');

        if (empty($data)) Response::error('No data to update');

        if (isset($data['phone']) && Customer::phoneExists($data['phone'], $id)) {
            Response::error('Phone number already registered', 409);
        }

        if (isset($data['is_active'])) $data['is_active'] = (int) $data['is_active'];

        Customer::update($id, $data);
        AppLogger::action(Auth::id(), 'update', 'customer', $id, $data);

        Response::success(null, 'Customer updated');
    }

    public function destroy(Request $req, int $id): void
    {
        if (!Customer::find($id)) throw new NotFoundException('Customer not found');

        Customer::update($id, ['is_active' => 0]);
        AppLogger::action(Auth::id(), 'delete', 'customer', $id, null);

        Response::success(null, 'Customer deactivated');
    }
}
