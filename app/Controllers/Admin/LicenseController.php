<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Activation;
use App\Models\License;
use App\Models\Product;
use App\Services\AuditService;
use App\Services\LicenseService;

/**
 * LicenseController
 *
 * Admin license management: list, view, create, status actions,
 * reset, renew, delete. Status/action endpoints support AJAX.
 */
class LicenseController extends Controller
{
    private License $licenses;
    private LicenseService $service;

    public function __construct()
    {
        $this->licenses = new License();
        $this->service  = new LicenseService();
    }

    public function index(Request $request): void
    {
        $this->licenses->expireOverdue();
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $filters = [
            'status'     => $request->query('status', ''),
            'product_id' => $request->query('product_id', ''),
            'search'     => trim((string) $request->query('search', '')),
        ];

        $rows  = $this->licenses->paginate($filters, $perPage, $offset);
        $total = $this->licenses->countFiltered($filters);

        $this->view('licenses/index', [
            'title'    => 'Licenses',
            'licenses' => $rows,
            'products' => (new Product())->all('product_name ASC'),
            'filters'  => $filters,
            'page'     => $page,
            'perPage'  => $perPage,
            'total'    => $total,
            'pages'    => (int) ceil($total / $perPage),
            'csrf'     => Csrf::token(),
            'flash'    => self::pullFlash(),
        ]);
    }

    public function createForm(Request $request): void
    {
        $this->view('licenses/form', [
            'title'    => 'Create License',
            'products' => (new Product())->all('product_name ASC'),
            'csrf'     => Csrf::token(),
            'flash'    => self::pullFlash(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->guardCsrf($request, '/admin/licenses/create');

        $v = Validator::make($request->all(), [
            'product_id'       => 'required|int',
            'customer_name'    => 'string|max:150',
            'customer_email'   => 'email',
            'domain'           => 'string|max:190',
            'ip_address'       => 'string|max:45',
            'activation_limit' => 'int',
            'expiry_date'      => 'date',
        ]);
        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/admin/licenses/create');
        }

        $result = $this->service->create($request->all());
        if ($result['status']) {
            AuditService::admin('license.created_admin', $result['data'], 'license', (string) ($result['data']['license_id'] ?? ''));
            $this->flash('success', 'License created: ' . ($result['data']['license_key'] ?? ''));
            $this->redirect('/admin/licenses');
        }
        $this->flash('error', $result['message']);
        $this->redirect('/admin/licenses/create');
    }

    public function editForm(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $license = $this->licenses->find($id);
        if ($license === null) {
            $this->flash('error', 'License not found.');
            $this->redirect('/admin/licenses');
        }

        $this->view('licenses/form', [
            'title'    => 'Edit License #' . $id,
            'license'  => $license,
            'products' => (new Product())->all('product_name ASC'),
            'csrf'     => Csrf::token(),
            'flash'    => self::pullFlash(),
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $this->guardCsrf($request, '/admin/licenses/' . $id . '/edit');

        $v = Validator::make($request->all(), [
            'product_id'       => 'required|int',
            'customer_name'    => 'string|max:150',
            'customer_email'   => 'email',
            'domain'           => 'string|max:190',
            'ip_address'       => 'string|max:45',
            'activation_limit' => 'int',
            'expiry_date'      => 'date',
        ]);
        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/admin/licenses/' . $id . '/edit');
        }

        $result = $this->service->update($id, $request->all());
        if ($result['status']) {
            AuditService::admin('license.updated_admin', $result['data'], 'license', (string) $id);
            $this->flash('success', 'License #' . $id . ' updated successfully.');
            $this->redirect('/admin/licenses/' . $id);
        }

        $this->flash('error', $result['message']);
        $this->redirect('/admin/licenses/' . $id . '/edit');
    }

    public function show(Request $request, array $params): void
    {
        $license = $this->licenses->find((int) ($params['id'] ?? 0));
        if ($license === null) {
            $this->flash('error', 'License not found.');
            $this->redirect('/admin/licenses');
        }
        $product     = (new Product())->find((int) $license['product_id']);
        $activations = (new Activation())->forLicense((int) $license['id']);

        $this->view('licenses/show', [
            'title'       => 'License Detail',
            'license'     => $license,
            'product'     => $product,
            'activations' => $activations,
            'csrf'        => Csrf::token(),
            'flash'       => self::pullFlash(),
        ]);
    }

    /**
     * AJAX action endpoint: suspend|unsuspend|terminate|reset|delete|renew.
     */
    public function action(Request $request, array $params): void
    {
        if (!Csrf::verify((string) $request->input('_csrf'))) {
            $this->json(['status' => false, 'message' => 'Invalid session token'], 419);
        }

        $id      = (int) ($params['id'] ?? 0);
        $action  = (string) ($params['action'] ?? '');
        $license = $this->licenses->find($id);
        if ($license === null) {
            $this->json(['status' => false, 'message' => 'License not found'], 404);
        }

        $key = (string) $license['license_key'];

        switch ($action) {
            case 'suspend':
                $r = $this->service->setStatus($key, 'suspended');
                break;
            case 'unsuspend':
                $r = $this->service->setStatus($key, 'active');
                break;
            case 'terminate':
                $r = $this->service->setStatus($key, 'terminated');
                break;
            case 'reset':
                $r = $this->service->reset($key);
                break;
            case 'renew':
                $r = $this->service->renew($key, (string) $request->input('expiry_date'));
                break;
            case 'delete':
                $this->licenses->deleteById($id);
                AuditService::admin('license.deleted', ['id' => $id, 'key' => $key], 'license', (string) $id);
                $this->json(['status' => true, 'message' => 'License deleted']);
                // no break needed; json() exits
            default:
                $this->json(['status' => false, 'message' => 'Unknown action'], 422);
        }

        AuditService::admin('license.' . $action, ['id' => $id, 'key' => $key], 'license', (string) $id);
        $this->json(['status' => $r['status'], 'message' => $r['message'], 'data' => $r['data']]);
    }

    private function guardCsrf(Request $request, string $redirect): void
    {
        if (!Csrf::verify((string) $request->input('_csrf'))) {
            $this->flash('error', 'Invalid session token.');
            $this->redirect($redirect);
        }
    }
}
