<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Validator;
use App\Services\LicenseService;

/**
 * LicenseApiController
 *
 * Handles the license lifecycle endpoints:
 * create, verify, activate, deactivate, renew, reset.
 */
class LicenseApiController extends ApiController
{
    private LicenseService $service;

    public function __construct()
    {
        $this->service = new LicenseService();
    }

    public function create(Request $request): void
    {
        $v = Validator::make($request->all(), [
            'product_id'       => 'required|int',
            'customer_name'    => 'string|max:150',
            'customer_email'   => 'email',
            'activation_limit' => 'int',
            'expiry_date'      => 'date',
        ]);
        if ($v->fails()) {
            $this->respond($request, false, $v->firstError(), [], 422);
        }

        $result = $this->service->create($request->all());
        $this->respondService($request, $result);
    }

    public function verify(Request $request): void
    {
        $v = Validator::make($request->all(), [
            'license_key' => 'required|string',
            'domain'      => 'string',
            'ip'          => 'ip',
        ]);
        if ($v->fails()) {
            $this->respond($request, false, $v->firstError(), [], 422);
        }

        $result = $this->service->verify(
            (string) $request->input('license_key'),
            $this->nullable($request->input('domain')),
            $this->nullable($request->input('ip')),
            $this->nullable($request->input('product'))
        );
        $this->respondService($request, $result);
    }

    public function activate(Request $request): void
    {
        $v = Validator::make($request->all(), [
            'license_key' => 'required|string',
            'domain'      => 'string',
            'ip'          => 'ip',
        ]);
        if ($v->fails()) {
            $this->respond($request, false, $v->firstError(), [], 422);
        }

        $result = $this->service->activate(
            (string) $request->input('license_key'),
            $this->nullable($request->input('domain')),
            $this->nullable($request->input('ip')),
            $this->nullable($request->input('product')),
            $this->nullable($request->input('server_hostname')),
            $this->nullable($request->input('install_path'))
        );
        $this->respondService($request, $result);
    }

    public function deactivate(Request $request): void
    {
        $result = $this->service->deactivate(
            (string) $request->input('license_key'),
            $this->nullable($request->input('domain'))
        );
        $this->respondService($request, $result);
    }

    public function renew(Request $request): void
    {
        $result = $this->service->renew(
            (string) $request->input('license_key'),
            $this->nullable($request->input('expiry_date') ?? $request->input('expiry'))
        );
        $this->respondService($request, $result);
    }

    public function reset(Request $request): void
    {
        $result = $this->service->reset((string) $request->input('license_key'));
        $this->respondService($request, $result);
    }

    private function nullable(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (string) $value;
    }
}
