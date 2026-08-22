<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Models\Product;

/**
 * ProductApiController
 *
 * Exposes product listings for WHMCS dropdowns and client integrations.
 */
class ProductApiController extends ApiController
{
    private Product $products;

    public function __construct()
    {
        $this->products = new Product();
    }

    public function index(Request $request): void
    {
        $rows = $this->products->where(['status' => 'active'], 'product_name ASC');
        $data = array_map(static fn($p) => [
            'id'             => (int) $p['id'],
            'product_name'   => $p['product_name'],
            'product_key'    => $p['product_key'],
            'latest_version' => $p['latest_version'] ?? null,
            'description'    => $p['description'] ?? null,
        ], $rows);

        $this->respond($request, true, 'Active products retrieved', [
            'products' => $data,
            'count'    => count($data),
        ]);
    }
}
