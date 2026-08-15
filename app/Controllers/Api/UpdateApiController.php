<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Models\License;
use App\Models\Product;

/**
 * UpdateApiController
 *
 * Reports the latest available version + download URL for a product,
 * optionally gated behind a valid, active license.
 */
class UpdateApiController extends ApiController
{
    public function check(Request $request): void
    {
        $productKey = (string) $request->input('product');
        if ($productKey === '') {
            $this->respond($request, false, 'product is required', [], 422);
        }

        $product = (new Product())->findByKey($productKey);
        if ($product === null) {
            $this->respond($request, false, 'Unknown product', [], 404);
        }

        // If a license key is supplied, ensure it is valid/active.
        $licenseKey = (string) $request->input('license_key');
        if ($licenseKey !== '') {
            $license = (new License())->findByKey($licenseKey);
            if ($license === null || $license['status'] !== 'active') {
                $this->respond($request, false, 'License not active', [], 403);
            }
        }

        $current = (string) $request->input('current_version');
        $latest  = (string) ($product['latest_version'] ?? '');
        $hasUpdate = $latest !== '' && ($current === '' || version_compare($latest, $current, '>'));

        $this->respond($request, true, 'OK', [
            'latest_version' => $latest,
            'download_url'   => $product['download_url'] ?? null,
            'update_notes'   => $product['update_notes'] ?? null,
            'update_available' => $hasUpdate,
        ]);
    }
}
