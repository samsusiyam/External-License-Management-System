<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Product;
use App\Services\AuditService;

/**
 * ProductController
 *
 * CRUD for products (admin panel).
 */
class ProductController extends Controller
{
    private Product $products;

    public function __construct()
    {
        $this->products = new Product();
    }

    public function index(Request $request): void
    {
        $this->view('products/index', [
            'title'    => 'Products',
            'products' => $this->products->all(),
            'flash'    => self::pullFlash(),
            'csrf'     => Csrf::token(),
        ]);
    }

    public function createForm(Request $request): void
    {
        $this->view('products/form', [
            'title'   => 'Add Product',
            'product' => null,
            'csrf'    => Csrf::token(),
            'flash'   => self::pullFlash(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->guardCsrf($request);
        $data = $this->validate($request);

        if ($this->products->keyExists($data['product_key'])) {
            $this->flash('error', 'Product key already exists.');
            $this->redirect('/admin/products/create');
        }

        $id = $this->products->create([
            'product_name'   => $data['product_name'],
            'product_key'    => $data['product_key'],
            'description'    => $data['description'],
            'latest_version' => $data['latest_version'],
            'download_url'   => $data['download_url'],
            'update_notes'   => $data['update_notes'],
            'status'         => $data['status'],
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        AuditService::admin('product.created', ['id' => $id, 'key' => $data['product_key']], 'product', (string) $id);
        $this->flash('success', 'Product created.');
        $this->redirect('/admin/products');
    }

    public function editForm(Request $request, array $params): void
    {
        $product = $this->products->find((int) ($params['id'] ?? 0));
        if ($product === null) {
            $this->flash('error', 'Product not found.');
            $this->redirect('/admin/products');
        }
        $this->view('products/form', [
            'title'   => 'Edit Product',
            'product' => $product,
            'csrf'    => Csrf::token(),
            'flash'   => self::pullFlash(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $this->guardCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        $product = $this->products->find($id);
        if ($product === null) {
            $this->flash('error', 'Product not found.');
            $this->redirect('/admin/products');
        }

        $data = $this->validate($request);
        if ($this->products->keyExists($data['product_key'], $id)) {
            $this->flash('error', 'Product key already used by another product.');
            $this->redirect('/admin/products/' . $id . '/edit');
        }

        $this->products->updateById($id, [
            'product_name'   => $data['product_name'],
            'product_key'    => $data['product_key'],
            'description'    => $data['description'],
            'latest_version' => $data['latest_version'],
            'download_url'   => $data['download_url'],
            'update_notes'   => $data['update_notes'],
            'status'         => $data['status'],
        ]);

        AuditService::admin('product.updated', ['id' => $id], 'product', (string) $id);
        $this->flash('success', 'Product updated.');
        $this->redirect('/admin/products');
    }

    public function delete(Request $request, array $params): void
    {
        $this->guardCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        $this->products->deleteById($id);
        AuditService::admin('product.deleted', ['id' => $id], 'product', (string) $id);
        $this->flash('success', 'Product deleted.');
        $this->redirect('/admin/products');
    }

    /**
     * @return array<string,string|null>
     */
    private function validate(Request $request): array
    {
        $v = Validator::make($request->all(), [
            'product_name' => 'required|string|max:150',
            'product_key'  => 'required|string|max:80',
            'status'       => 'required|in:active,inactive',
        ]);
        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect($request->header('referer') ?? '/admin/products');
        }
        return [
            'product_name'   => trim((string) $request->input('product_name')),
            'product_key'    => trim((string) $request->input('product_key')),
            'description'    => $this->nullable($request->input('description')),
            'latest_version' => $this->nullable($request->input('latest_version')),
            'download_url'   => $this->nullable($request->input('download_url')),
            'update_notes'   => $this->nullable($request->input('update_notes')),
            'status'         => (string) $request->input('status'),
        ];
    }

    private function nullable(mixed $v): ?string
    {
        $v = is_string($v) ? trim($v) : $v;
        return ($v === null || $v === '') ? null : (string) $v;
    }

    private function guardCsrf(Request $request): void
    {
        if (!Csrf::verify((string) $request->input('_csrf'))) {
            $this->flash('error', 'Invalid session token.');
            $this->redirect('/admin/products');
        }
    }
}
