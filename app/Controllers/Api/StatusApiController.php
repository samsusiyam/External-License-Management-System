<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Services\LicenseService;

/**
 * StatusApiController
 *
 * License status transitions triggered by WHMCS hooks or admin tooling:
 * suspend, unsuspend, terminate.
 */
class StatusApiController extends ApiController
{
    private LicenseService $service;

    public function __construct()
    {
        $this->service = new LicenseService();
    }

    public function suspend(Request $request): void
    {
        $this->transition($request, 'suspended');
    }

    public function unsuspend(Request $request): void
    {
        $this->transition($request, 'active');
    }

    public function terminate(Request $request): void
    {
        $this->transition($request, 'terminated');
    }

    private function transition(Request $request, string $status): void
    {
        $licenseKey = (string) $request->input('license_key');
        if ($licenseKey === '') {
            $this->respond($request, false, 'license_key is required', [], 422);
        }
        $result = $this->service->setStatus($licenseKey, $status);
        $this->respondService($request, $result);
    }
}
