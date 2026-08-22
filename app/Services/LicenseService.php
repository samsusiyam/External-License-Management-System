<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Activation;
use App\Models\License;
use App\Models\Product;

/**
 * LicenseService
 *
 * Core business logic for the license lifecycle: create, verify,
 * activate, deactivate, renew, reset, and status transitions.
 *
 * Every method returns a structured array:
 *   ['status' => bool, 'message' => string, 'data' => array]
 */
class LicenseService
{
    private License $licenses;
    private Product $products;
    private Activation $activations;

    public function __construct()
    {
        $this->licenses    = new License();
        $this->products    = new Product();
        $this->activations = new Activation();
    }

    /**
     * Create a new license.
     *
     * @param array<string,mixed> $data
     * @return array{status:bool,message:string,data:array<string,mixed>}
     */
    public function create(array $data): array
    {
        $identifier = $data['product_id'] ?? ($data['product_key'] ?? ($data['product'] ?? ($data['product_name'] ?? null)));
        $product = $this->products->resolveSmart($identifier);
        if ($product === null) {
            return $this->fail('Invalid product');
        }

        // Generate a unique license key.
        do {
            $key = KeyGenerator::licenseKey();
        } while ($this->licenses->keyExists($key));

        $domain = !empty($data['domain']) ? trim((string) $data['domain']) : null;
        $ip     = !empty($data['ip_address']) ? trim((string) $data['ip_address']) : (!empty($data['ip']) ? trim((string) $data['ip']) : null);

        $row = [
            'license_key'      => $key,
            'product_id'       => (int) $product['id'],
            'customer_name'    => $data['customer_name'] ?? ($data['customer'] ?? null),
            'customer_email'   => $data['customer_email'] ?? null,
            'whmcs_service_id' => isset($data['whmcs_service_id']) ? (int) $data['whmcs_service_id'] : null,
            'domain'           => $domain,
            'ip_address'       => $ip,
            'activation_limit' => isset($data['activation_limit']) ? max(1, (int) $data['activation_limit']) : 1,
            'domain_lock'      => !empty($data['domain_lock']) ? 1 : 0,
            'ip_lock'          => !empty($data['ip_lock']) ? 1 : 0,
            'expiry_date'      => $this->normalizeDate($data['expiry_date'] ?? ($data['expiry'] ?? null)),
            'status'           => 'active',
            'notes'            => $data['notes'] ?? null,
            'created_at'       => date('Y-m-d H:i:s'),
        ];

        $id = $this->licenses->create($row);

        AuditService::log('license.created', 'api', null, 'license', (string) $id, [
            'license_key' => $key,
            'product_id'  => $product['id'],
        ]);

        return $this->ok('License created', [
            'license_key' => $key,
            'license_id'  => $id,
            'expiry'      => $row['expiry_date'],
            'product'     => $product['product_key'],
        ]);
    }

    /**
     * Update an existing license.
     *
     * @param int $id
     * @param array<string,mixed> $data
     * @return array{status:bool,message:string,data:array<string,mixed>}
     */
    public function update(int $id, array $data): array
    {
        $license = $this->licenses->find($id);
        if ($license === null) {
            return $this->fail('License not found');
        }

        $productId = isset($data['product_id']) ? (int) $data['product_id'] : (int) $license['product_id'];
        $product = $this->products->find($productId);
        if ($product === null) {
            return $this->fail('Invalid product');
        }

        $domain = array_key_exists('domain', $data) ? ($data['domain'] !== '' ? trim((string) $data['domain']) : null) : $license['domain'];
        $ip = array_key_exists('ip_address', $data) ? ($data['ip_address'] !== '' ? trim((string) $data['ip_address']) : null) : $license['ip_address'];

        $fields = [
            'product_id'       => $productId,
            'customer_name'    => $data['customer_name'] ?? $license['customer_name'],
            'customer_email'   => $data['customer_email'] ?? $license['customer_email'],
            'domain'           => $domain,
            'ip_address'       => $ip,
            'activation_limit' => isset($data['activation_limit']) ? max(1, (int) $data['activation_limit']) : (int) $license['activation_limit'],
            'domain_lock'      => isset($data['domain_lock']) ? (!empty($data['domain_lock']) ? 1 : 0) : 0,
            'ip_lock'          => isset($data['ip_lock']) ? (!empty($data['ip_lock']) ? 1 : 0) : 0,
            'expiry_date'      => array_key_exists('expiry_date', $data) ? $this->normalizeDate($data['expiry_date']) : $license['expiry_date'],
            'status'           => in_array($data['status'] ?? '', ['active', 'suspended', 'expired', 'terminated'], true) ? $data['status'] : $license['status'],
            'notes'            => $data['notes'] ?? $license['notes'],
        ];

        $this->licenses->updateById($id, $fields);

        AuditService::log('license.updated', 'admin', null, 'license', (string) $id, [
            'license_key' => $license['license_key'],
            'changes'     => array_diff_assoc($fields, $license),
        ]);

        return $this->ok('License updated successfully', [
            'license_id'  => $id,
            'license_key' => $license['license_key'],
        ]);
    }

    /**
     * Verify a license without registering an activation.
     *
     * @return array{status:bool,message:string,data:array<string,mixed>}
     */
    public function verify(string $licenseKey, ?string $domain, ?string $ip, ?string $productKey): array
    {
        $check = $this->resolveAndValidate($licenseKey, $productKey, $domain, $ip);
        if (!$check['status']) {
            return $check;
        }
        /** @var array<string,mixed> $license */
        $license = $check['data']['license'];
        $product = $this->products->find((int) $license['product_id']);

        return $this->ok('License Valid', [
            'license_key'      => $license['license_key'],
            'status'           => $license['status'],
            'expiry'           => $license['expiry_date'],
            'activation_limit' => (int) $license['activation_limit'],
            'activation_count' => (int) $license['activation_count'],
            'product_name'     => $product['product_name'] ?? null,
            'product_key'      => $product['product_key'] ?? null,
            'latest_version'   => $product['latest_version'] ?? null,
            'download_url'     => $product['download_url'] ?? null,
            'update_notes'     => $product['update_notes'] ?? null,
        ]);
    }

    /**
     * Activate a license for a specific install.
     *
     * @return array{status:bool,message:string,data:array<string,mixed>}
     */
    public function activate(string $licenseKey, ?string $domain, ?string $ip, ?string $productKey, ?string $hostname, ?string $installPath): array
    {
        $db = Database::instance();
        $db->beginTransaction();
        try {
            $check = $this->resolveAndValidate($licenseKey, $productKey, $domain, $ip);
            if (!$check['status']) {
                $db->rollBack();
                return $check;
            }
            /** @var array<string,mixed> $license */
            $license   = $check['data']['license'];
            $licenseId = (int) $license['id'];

            // Serialise activation-limit handling: lock the license row so a
            // concurrent request cannot pass the limit check before we insert.
            $this->licenses->lockForUpdate($licenseId);

            // Already activated for this domain? Refresh and return success.
            $existing = $this->activations->findActive($licenseId, $domain);
            if ($existing !== null) {
                $this->activations->touchCheck((int) $existing['id']);
                $db->commit();
                return $this->ok('Already activated', [
                    'activation_id' => (int) $existing['id'],
                    'expiry'        => $license['expiry_date'],
                ]);
            }

            // Enforce activation limit.
            $activeCount = $this->activations->activeCount($licenseId);
            if ($activeCount >= (int) $license['activation_limit']) {
                $db->rollBack();
                AuditService::log('license.activation_denied', 'api', null, 'license', (string) $licenseId, [
                    'reason' => 'limit_reached', 'domain' => $domain,
                ]);
                return $this->fail('Activation limit reached');
            }

            // Register activation.
            $activationId = $this->activations->create([
                'license_id'      => $licenseId,
                'domain'          => $domain,
                'ip'              => $ip,
                'server_hostname' => $hostname,
                'install_path'    => $installPath,
                'status'          => 'active',
                'activated_at'    => date('Y-m-d H:i:s'),
                'last_check'      => date('Y-m-d H:i:s'),
            ]);

            // Update license counters / binding on first activation.
            $update = ['activation_count' => $activeCount + 1];
            if (empty($license['domain']) && $domain !== null) {
                $update['domain'] = $domain;
            }
            if (empty($license['ip_address']) && $ip !== null) {
                $update['ip_address'] = $ip;
            }
            if (empty($license['install_path']) && $installPath !== null) {
                $update['install_path'] = $installPath;
            }
            $this->licenses->updateById($licenseId, $update);

            $db->commit();

            AuditService::log('license.activated', 'api', null, 'license', (string) $licenseId, [
                'domain' => $domain, 'ip' => $ip,
            ]);

            return $this->ok('License activated', [
                'activation_id'    => $activationId,
                'expiry'           => $license['expiry_date'],
                'activation_count' => $activeCount + 1,
                'activation_limit' => (int) $license['activation_limit'],
            ]);
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[ELMS activate] ' . $e->getMessage());
            return $this->fail('Activation failed');
        }
    }

    /**
     * Deactivate an install (free one activation slot).
     *
     * @return array{status:bool,message:string,data:array<string,mixed>}
     */
    public function deactivate(string $licenseKey, ?string $domain): array
    {
        $license = $this->licenses->findByKey($licenseKey);
        if ($license === null) {
            return $this->fail('License Invalid');
        }
        $licenseId = (int) $license['id'];

        $activation = $this->activations->findActive($licenseId, $domain);
        if ($activation === null) {
            return $this->fail('No matching activation found');
        }

        $this->activations->updateById((int) $activation['id'], ['status' => 'deactivated']);
        $newCount = max(0, $this->activations->activeCount($licenseId));
        $this->licenses->updateById($licenseId, ['activation_count' => $newCount]);

        AuditService::log('license.deactivated', 'api', null, 'license', (string) $licenseId, ['domain' => $domain]);

        return $this->ok('License deactivated', ['activation_count' => $newCount]);
    }

    /**
     * Renew / extend a license expiry.
     *
     * @return array{status:bool,message:string,data:array<string,mixed>}
     */
    public function renew(string $licenseKey, ?string $newExpiry): array
    {
        $license = $this->licenses->findByKey($licenseKey);
        if ($license === null) {
            return $this->fail('License Invalid');
        }
        $expiry = $this->normalizeDate($newExpiry);
        if ($expiry === null) {
            return $this->fail('Invalid expiry date');
        }

        $status = $license['status'] === 'expired' ? 'active' : $license['status'];
        $this->licenses->updateById((int) $license['id'], [
            'expiry_date' => $expiry,
            'status'      => $status,
        ]);

        AuditService::log('license.renewed', 'api', null, 'license', (string) $license['id'], ['expiry' => $expiry]);

        return $this->ok('License renewed', ['expiry' => $expiry, 'status' => $status]);
    }

    /**
     * Reset domain / IP / activation bindings.
     *
     * @return array{status:bool,message:string,data:array<string,mixed>}
     */
    public function reset(string $licenseKey): array
    {
        $license = $this->licenses->findByKey($licenseKey);
        if ($license === null) {
            return $this->fail('License Invalid');
        }
        $licenseId = (int) $license['id'];

        $this->activations->deactivateForLicense($licenseId);
        $this->licenses->updateById($licenseId, [
            'domain'           => null,
            'ip_address'       => null,
            'install_path'     => null,
            'activation_count' => 0,
        ]);

        AuditService::log('license.reset', 'api', null, 'license', (string) $licenseId, []);

        return $this->ok('License reset', []);
    }

    /**
     * Change license status (suspend / unsuspend / terminate).
     *
     * @return array{status:bool,message:string,data:array<string,mixed>}
     */
    public function setStatus(string $licenseKey, string $status): array
    {
        $valid = ['active', 'suspended', 'terminated'];
        if (!in_array($status, $valid, true)) {
            return $this->fail('Invalid status');
        }
        $license = $this->licenses->findByKey($licenseKey);
        if ($license === null) {
            return $this->fail('License Invalid');
        }

        $this->licenses->updateById((int) $license['id'], ['status' => $status]);
        AuditService::log('license.status_' . $status, 'api', null, 'license', (string) $license['id'], []);

        return $this->ok('License status updated', ['status' => $status]);
    }

    /**
     * Shared resolution + validation pipeline used by verify/activate.
     * Order: exists -> product match -> status -> expiry -> domain lock -> ip lock.
     *
     * @return array{status:bool,message:string,data:array<string,mixed>}
     */
    private function resolveAndValidate(string $licenseKey, ?string $productKey, ?string $domain, ?string $ip): array
    {
        $license = $this->licenses->findByKey($licenseKey);
        if ($license === null) {
            return $this->fail('License Invalid');
        }

        // Product match (if provided).
        if ($productKey !== null && trim($productKey) !== '') {
            $product = $this->products->find((int) $license['product_id']);
            $match = false;
            if ($product !== null) {
                $target = strtolower(trim($productKey));
                $pKey   = strtolower(trim((string) $product['product_key']));
                $pName  = strtolower(trim((string) $product['product_name']));
                if ($target === $pKey || $target === $pName || (is_numeric($productKey) && (int) $productKey === (int) $product['id'])) {
                    $match = true;
                }
            }
            if (!$match) {
                return $this->fail('Product mismatch');
            }
        }

        // Status checks.
        if ($license['status'] === 'suspended') {
            return $this->fail('License Suspended');
        }
        if ($license['status'] === 'terminated') {
            return $this->fail('License Terminated');
        }

        // Expiry check.
        if (!empty($license['expiry_date'])) {
            $expiryTs = strtotime($license['expiry_date'] . ' 23:59:59');
            if ($expiryTs !== false && $expiryTs < time()) {
                if ($license['status'] !== 'expired') {
                    $this->licenses->updateById((int) $license['id'], ['status' => 'expired']);
                }
                return $this->fail('License Expired');
            }
        }

        // Domain check (enforced if license is bound to a domain or domain_lock is active)
        if (!empty($license['domain']) && $license['domain'] !== '*') {
            if ($domain === null || $domain === '' || !$this->domainMatches((string) $license['domain'], $domain)) {
                return $this->fail('Domain mismatch: License registered for ' . $license['domain']);
            }
        }

        // IP check (enforced if ip_lock is enabled or ip_address is bound)
        if ((int) $license['ip_lock'] === 1 && !empty($license['ip_address']) && $license['ip_address'] !== '*') {
            if ($ip === null || $ip === '' || !hash_equals((string) $license['ip_address'], $ip)) {
                return $this->fail('IP mismatch: License registered for IP ' . $license['ip_address']);
            }
        }

        return $this->ok('License Valid', ['license' => $license]);
    }

    private function domainMatches(string $bound, string $given): bool
    {
        $normalize = static function (string $d): string {
            $d = strtolower(trim($d));
            $d = preg_replace('#^https?://#', '', $d) ?? $d;
            $d = explode('/', $d)[0];
            return preg_replace('/^www\./', '', $d) ?? $d;
        };
        return $normalize($bound) === $normalize($given);
    }

    private function normalizeDate(?string $date): ?string
    {
        if ($date === null || $date === '' || $date === '0000-00-00' || str_starts_with($date, '0000')) {
            return null;
        }
        $ts = strtotime($date);
        return ($ts === false || $ts <= 0) ? null : date('Y-m-d', $ts);
    }

    /**
     * @param array<string,mixed> $data
     * @return array{status:bool,message:string,data:array<string,mixed>}
     */
    private function ok(string $message, array $data): array
    {
        return ['status' => true, 'message' => $message, 'data' => $data];
    }

    /**
     * @return array{status:bool,message:string,data:array<string,mixed>}
     */
    private function fail(string $message): array
    {
        return ['status' => false, 'message' => $message, 'data' => []];
    }
}
