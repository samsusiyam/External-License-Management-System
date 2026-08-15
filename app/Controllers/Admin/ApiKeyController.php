<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\ApiKey;
use App\Services\AuditService;
use App\Services\KeyGenerator;

/**
 * ApiKeyController
 *
 * Manage API credentials. The secret is only shown once, at creation.
 */
class ApiKeyController extends Controller
{
    private ApiKey $keys;

    public function __construct()
    {
        $this->keys = new ApiKey();
    }

    public function index(Request $request): void
    {
        $newKey = $_SESSION['_new_api_key'] ?? null;
        unset($_SESSION['_new_api_key']);

        $this->view('apikeys/index', [
            'title'   => 'API Keys',
            'keys'    => $this->keys->all(),
            'csrf'    => Csrf::token(),
            'flash'   => self::pullFlash(),
            'newKey'  => $newKey,
        ]);
    }

    public function store(Request $request): void
    {
        $this->guardCsrf($request);
        $name = trim((string) $request->input('name'));
        if ($name === '') {
            $this->flash('error', 'Name is required.');
            $this->redirect('/admin/apikeys');
        }

        $apiKey    = KeyGenerator::apiKey();
        $apiSecret = KeyGenerator::apiSecret();

        $id = $this->keys->create([
            'name'       => $name,
            'api_key'    => $apiKey,
            'secret_key' => $apiSecret,
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        AuditService::admin('apikey.created', ['id' => $id, 'name' => $name], 'api_key', (string) $id);

        // Show secret once via flash-style session var.
        $_SESSION['_new_api_key'] = ['api_key' => $apiKey, 'secret_key' => $apiSecret];
        $this->flash('success', 'API key created. Copy the secret now — it will not be shown again.');
        $this->redirect('/admin/apikeys');
    }

    public function revoke(Request $request, array $params): void
    {
        $this->guardCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        $this->keys->updateById($id, ['status' => 'revoked']);
        AuditService::admin('apikey.revoked', ['id' => $id], 'api_key', (string) $id);
        $this->flash('success', 'API key revoked.');
        $this->redirect('/admin/apikeys');
    }

    public function activate(Request $request, array $params): void
    {
        $this->guardCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        $this->keys->updateById($id, ['status' => 'active']);
        AuditService::admin('apikey.activated', ['id' => $id], 'api_key', (string) $id);
        $this->flash('success', 'API key re-activated.');
        $this->redirect('/admin/apikeys');
    }

    public function delete(Request $request, array $params): void
    {
        $this->guardCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        $this->keys->deleteById($id);
        AuditService::admin('apikey.deleted', ['id' => $id], 'api_key', (string) $id);
        $this->flash('success', 'API key deleted.');
        $this->redirect('/admin/apikeys');
    }

    private function guardCsrf(Request $request): void
    {
        if (!Csrf::verify((string) $request->input('_csrf'))) {
            $this->flash('error', 'Invalid session token.');
            $this->redirect('/admin/apikeys');
        }
    }
}
