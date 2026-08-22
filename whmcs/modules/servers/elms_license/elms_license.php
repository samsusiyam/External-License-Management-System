<?php

/**
 * ELMS License Server — WHMCS Provisioning Module
 *
 * This module allows WHMCS products to provision software licenses like a
 * standard server/cPanel module. When an order is placed, customer enters their
 * domain, and WHMCS automatically creates, suspends, unsuspends, terminates,
 * and displays the license key directly inside the WHMCS Client Area & Admin Panel.
 *
 * @package    ELMS
 * @version    2.0.0
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

/**
 * Module metadata definition.
 *
 * @return array<string,mixed>
 */
function elms_license_MetaData()
{
    return [
        'DisplayName'       => 'ELMS License Server',
        'APIVersion'        => '1.1',
        'RequiresServer'    => false,
        'DefaultNonSSLPort' => '80',
        'DefaultSSLPort'    => '443',
    ];
}

/**
 * Module configuration options shown in WHMCS:
 * Setup > Products/Services > Products/Services > Edit Product > Module Settings
 *
 * @return array<string,mixed>
 */
function elms_license_ConfigOptions()
{
    return [
        'License Server URL' => [
            'Type'        => 'text',
            'Size'        => '60',
            'Default'     => 'https://lic.yourdomain.com',
            'Description' => 'Base URL of your ELMS server (without trailing slash).',
        ],
        'API Key' => [
            'Type'        => 'text',
            'Size'        => '50',
            'Description' => 'Public API key from ELMS > API Keys.',
        ],
        'API Secret' => [
            'Type'        => 'password',
            'Size'        => '60',
            'Description' => 'Secret key paired with the API key.',
        ],
        'ELMS Product Key / ID' => [
            'Type'        => 'text',
            'Size'        => '30',
            'Description' => 'Product Key or ID on ELMS. Leave blank to auto-match WHMCS product name.',
        ],
        'Activation Limit' => [
            'Type'        => 'text',
            'Size'        => '5',
            'Default'     => '1',
            'Description' => 'Maximum allowed concurrent activations (default: 1).',
        ],
        'Enforce Domain Lock' => [
            'Type'        => 'dropdown',
            'Options'     => 'Yes,No',
            'Default'     => 'Yes',
            'Description' => 'Lock license to the customer-specified domain.',
        ],
        'Enforce IP Lock' => [
            'Type'        => 'dropdown',
            'Options'     => 'No,Yes',
            'Default'     => 'No',
            'Description' => 'Lock license to the customer server IP address.',
        ],
    ];
}

/**
 * 1-Click Test Connection from Module Settings in WHMCS.
 *
 * @param array<string,mixed> $params
 * @return array<string,mixed>
 */
function elms_license_TestConnection(array $params)
{
    $serverUrl = rtrim((string) ($params['configoption1'] ?? ''), '/');
    $apiKey    = (string) ($params['configoption2'] ?? '');
    $apiSecret = (string) ($params['configoption3'] ?? '');

    if ($serverUrl === '' || $apiKey === '' || $apiSecret === '') {
        return [
            'success' => false,
            'error'   => 'Please provide License Server URL, API Key, and API Secret.',
        ];
    }

    $res = elms_server_api_call($serverUrl, $apiKey, $apiSecret, '/api/license/verify', [
        'license_key' => 'TEST-PING-' . time(),
        'domain'      => 'ping.test',
    ]);

    if (isset($res['status'])) {
        return [
            'success' => true,
            'data'    => 'Connected successfully to ' . $serverUrl,
        ];
    }

    return [
        'success' => false,
        'error'   => $res['message'] ?? 'Connection error',
    ];
}

/**
 * Provision / Create License when order is approved/paid in WHMCS.
 *
 * @param array<string,mixed> $params
 * @return string 'success' or error message
 */
function elms_license_CreateAccount(array $params)
{
    $serverUrl       = rtrim((string) ($params['configoption1'] ?? ''), '/');
    $apiKey          = (string) ($params['configoption2'] ?? '');
    $apiSecret       = (string) ($params['configoption3'] ?? '');
    $productConfig   = trim((string) ($params['configoption4'] ?? ''));
    $activationLimit = max(1, (int) ($params['configoption5'] ?? 1));
    $domainLock      = ($params['configoption6'] ?? 'Yes') === 'Yes' ? 1 : 0;
    $ipLock          = ($params['configoption7'] ?? 'No') === 'Yes' ? 1 : 0;

    $serviceId = (int) $params['serviceid'];
    $domain    = trim((string) ($params['domain'] ?? ''));
    $expiry    = $params['model']->nextduedate ?? null;

    $customerName = trim(($params['clientsdetails']['firstname'] ?? '') . ' ' . ($params['clientsdetails']['lastname'] ?? ''));
    $customerEmail = $params['clientsdetails']['email'] ?? null;

    $payload = [
        'whmcs_service_id' => $serviceId,
        'customer_name'    => $customerName ?: 'Client #' . ($params['clientsdetails']['userid'] ?? $serviceId),
        'customer_email'   => $customerEmail,
        'domain'           => $domain ?: null,
        'expiry_date'      => $expiry,
        'activation_limit' => $activationLimit,
        'domain_lock'      => $domainLock,
        'ip_lock'          => $ipLock,
    ];

    if ($productConfig !== '') {
        if (is_numeric($productConfig)) {
            $payload['product_id'] = (int) $productConfig;
        } else {
            $payload['product'] = $productConfig;
        }
    } else {
        $payload['product'] = $params['package'] ?? '';
    }

    $res = elms_server_api_call($serverUrl, $apiKey, $apiSecret, '/api/license/create', $payload);

    if (!empty($res['status']) && !empty($res['data']['license_key'])) {
        $licKey  = (string) $res['data']['license_key'];
        $prodKey = (string) ($res['data']['product'] ?? '');

        // Store license key in WHMCS Service password & username fields
        Capsule::table('tblhosting')
            ->where('id', $serviceId)
            ->update([
                'password' => encrypt($licKey),
                'username' => $licKey,
            ]);

        // Also track in mod_elms_licenses
        try {
            if (!Capsule::schema()->hasTable('mod_elms_licenses')) {
                Capsule::schema()->create('mod_elms_licenses', function ($table) {
                    $table->increments('id');
                    $table->integer('service_id')->unique();
                    $table->string('license_key', 64)->nullable()->index();
                    $table->string('product_key', 80)->nullable();
                    $table->string('status', 20)->default('active');
                    $table->timestamp('created_at')->nullable();
                });
            }
            Capsule::table('mod_elms_licenses')->updateOrInsert(
                ['service_id' => $serviceId],
                ['license_key' => $licKey, 'product_key' => $prodKey, 'status' => 'active', 'created_at' => date('Y-m-d H:i:s')]
            );
        } catch (\Throwable $e) {
            // ignore
        }

        return 'success';
    }

    return 'ELMS Provisioning Failed: ' . ($res['message'] ?? 'Unknown Error');
}

/**
 * Suspend License.
 *
 * @param array<string,mixed> $params
 * @return string 'success' or error message
 */
function elms_license_SuspendAccount(array $params)
{
    $serverUrl = rtrim((string) ($params['configoption1'] ?? ''), '/');
    $apiKey    = (string) ($params['configoption2'] ?? '');
    $apiSecret = (string) ($params['configoption3'] ?? '');
    $licKey    = elms_server_get_license_key($params);

    if ($licKey === '') {
        return 'No License Key assigned to this service.';
    }

    $res = elms_server_api_call($serverUrl, $apiKey, $apiSecret, '/api/license/suspend', [
        'license_key' => $licKey,
    ]);

    if (!empty($res['status'])) {
        try {
            Capsule::table('mod_elms_licenses')->where('license_key', $licKey)->update(['status' => 'suspended']);
        } catch (\Throwable $e) {}
        return 'success';
    }

    return 'Suspend Failed: ' . ($res['message'] ?? 'Unknown Error');
}

/**
 * Unsuspend License.
 *
 * @param array<string,mixed> $params
 * @return string 'success' or error message
 */
function elms_license_UnsuspendAccount(array $params)
{
    $serverUrl = rtrim((string) ($params['configoption1'] ?? ''), '/');
    $apiKey    = (string) ($params['configoption2'] ?? '');
    $apiSecret = (string) ($params['configoption3'] ?? '');
    $licKey    = elms_server_get_license_key($params);

    if ($licKey === '') {
        return 'No License Key assigned to this service.';
    }

    $res = elms_server_api_call($serverUrl, $apiKey, $apiSecret, '/api/license/unsuspend', [
        'license_key' => $licKey,
    ]);

    if (!empty($res['status'])) {
        try {
            Capsule::table('mod_elms_licenses')->where('license_key', $licKey)->update(['status' => 'active']);
        } catch (\Throwable $e) {}
        return 'success';
    }

    return 'Unsuspend Failed: ' . ($res['message'] ?? 'Unknown Error');
}

/**
 * Terminate License.
 *
 * @param array<string,mixed> $params
 * @return string 'success' or error message
 */
function elms_license_TerminateAccount(array $params)
{
    $serverUrl = rtrim((string) ($params['configoption1'] ?? ''), '/');
    $apiKey    = (string) ($params['configoption2'] ?? '');
    $apiSecret = (string) ($params['configoption3'] ?? '');
    $licKey    = elms_server_get_license_key($params);

    if ($licKey === '') {
        return 'success';
    }

    $res = elms_server_api_call($serverUrl, $apiKey, $apiSecret, '/api/license/terminate', [
        'license_key' => $licKey,
    ]);

    if (!empty($res['status'])) {
        try {
            Capsule::table('mod_elms_licenses')->where('license_key', $licKey)->update(['status' => 'terminated']);
        } catch (\Throwable $e) {}
        return 'success';
    }

    return 'Terminate Failed: ' . ($res['message'] ?? 'Unknown Error');
}

/**
 * Renew License (Extend Expiry).
 *
 * @param array<string,mixed> $params
 * @return string 'success' or error message
 */
function elms_license_Renew(array $params)
{
    $serverUrl = rtrim((string) ($params['configoption1'] ?? ''), '/');
    $apiKey    = (string) ($params['configoption2'] ?? '');
    $apiSecret = (string) ($params['configoption3'] ?? '');
    $licKey    = elms_server_get_license_key($params);
    $newExpiry = $params['model']->nextduedate ?? null;

    if ($licKey === '' || $newExpiry === null) {
        return 'success';
    }

    $res = elms_server_api_call($serverUrl, $apiKey, $apiSecret, '/api/license/renew', [
        'license_key' => $licKey,
        'expiry_date' => $newExpiry,
    ]);

    return !empty($res['status']) ? 'success' : ('Renew Failed: ' . ($res['message'] ?? 'Unknown Error'));
}

/**
 * Custom Admin Action Buttons on Service View.
 *
 * @return array<string,string>
 */
function elms_license_AdminCustomButtonArray()
{
    return [
        'Verify License'  => 'VerifyLicenseStatus',
        'Reissue License' => 'ReissueLicenseKey',
        'Reset Bindings'  => 'ResetLicenseBindings',
    ];
}

function elms_license_VerifyLicenseStatus(array $params)
{
    $serverUrl = rtrim((string) ($params['configoption1'] ?? ''), '/');
    $apiKey    = (string) ($params['configoption2'] ?? '');
    $apiSecret = (string) ($params['configoption3'] ?? '');
    $licKey    = elms_server_get_license_key($params);

    if ($licKey === '') {
        return 'Error: No license key found for this service.';
    }

    $res = elms_server_api_call($serverUrl, $apiKey, $apiSecret, '/api/license/verify', [
        'license_key' => $licKey,
        'domain'      => $params['domain'] ?? null,
    ]);

    return !empty($res['status']) ? ('License Valid! Status: ' . ($res['data']['status'] ?? 'active') . ', Expiry: ' . ($res['data']['expiry'] ?? 'Never')) : ('License Error: ' . ($res['message'] ?? 'Invalid'));
}

function elms_license_ReissueLicenseKey(array $params)
{
    return elms_license_CreateAccount($params);
}

function elms_license_ResetLicenseBindings(array $params)
{
    $serverUrl = rtrim((string) ($params['configoption1'] ?? ''), '/');
    $apiKey    = (string) ($params['configoption2'] ?? '');
    $apiSecret = (string) ($params['configoption3'] ?? '');
    $licKey    = elms_server_get_license_key($params);

    if ($licKey === '') {
        return 'Error: No license key found.';
    }

    $res = elms_server_api_call($serverUrl, $apiKey, $apiSecret, '/api/license/reset', [
        'license_key' => $licKey,
    ]);

    return !empty($res['status']) ? 'Domain and IP bindings reset successfully.' : ('Reset Failed: ' . ($res['message'] ?? 'Error'));
}

/**
 * Display license details in WHMCS Admin Service page (clientsservices.php).
 *
 * @param array<string,mixed> $params
 * @return array<string,string>
 */
function elms_license_AdminServicesTabFields(array $params)
{
    $licKey = elms_server_get_license_key($params);

    return [
        'License Key' => '<strong style="font-family:monospace; font-size:14px; color:#1e293b; background:#f1f5f9; padding:4px 8px; border-radius:4px;">' . htmlspecialchars($licKey ?: 'Not Generated Yet') . '</strong>',
        'Assigned Domain' => htmlspecialchars($params['domain'] ?: 'Any Domain'),
        'License Server' => htmlspecialchars($params['configoption1'] ?? 'Not Configured'),
    ];
}

/**
 * Display license key & details directly inside WHMCS Client Area Product Details page.
 *
 * @param array<string,mixed> $params
 * @return array<string,mixed>
 */
function elms_license_ClientArea(array $params)
{
    $licKey    = elms_server_get_license_key($params);
    $serverUrl = rtrim((string) ($params['configoption1'] ?? ''), '/');
    $prodKey   = (string) ($params['configoption4'] ?? ($params['package'] ?? ''));

    return [
        'tabOverviewReplacementTemplate' => 'clientarea.tpl',
        'templateVariables' => [
            'license_key'   => $licKey,
            'domain'        => $params['domain'] ?? '',
            'service_status'=> $params['status'] ?? 'Active',
            'nextduedate'   => $params['nextduedate'] ?? 'Perpetual',
            'server_url'    => $serverUrl,
            'product_name'  => $params['package'] ?? 'Software License',
            'product_key'   => $prodKey,
        ],
    ];
}

// ---------------------------------------------------------------------------
// Internal Helpers
// ---------------------------------------------------------------------------

function elms_server_get_license_key(array $params): string
{
    $key = '';
    if (!empty($params['password'])) {
        $decrypted = decrypt($params['password']);
        if (!empty($decrypted)) {
            $key = $decrypted;
        }
    }
    if ($key === '' && !empty($params['username'])) {
        $key = $params['username'];
    }
    if ($key === '' && !empty($params['serviceid'])) {
        try {
            $row = Capsule::table('mod_elms_licenses')->where('service_id', (int) $params['serviceid'])->first();
            if ($row && !empty($row->license_key)) {
                $key = $row->license_key;
            }
        } catch (\Throwable $e) {}
    }
    return trim($key);
}

function elms_server_api_call(string $baseUrl, string $apiKey, string $apiSecret, string $path, array $payload): array
{
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $ts   = time();
    $sig  = hash_hmac('sha256', $ts . '.' . $apiKey . '.' . hash('sha256', $body), $apiSecret);

    $ch = curl_init(rtrim($baseUrl, '/') . $path);
    $respHeaders = [];
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 ELMS-WHMCS-Provisioner/2.0',
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Api-Key: ' . $apiKey,
            'X-Timestamp: ' . $ts,
            'X-Signature: ' . $sig,
        ],
        CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$respHeaders): int {
            $raw = $line;
            $line = trim($line);
            if ($line !== '' && !str_starts_with($line, 'HTTP/')) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
            }
            return strlen($raw);
        },
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['status' => false, 'message' => 'Connection error: ' . $err];
    }

    $decoded = json_decode($resp, true);
    return is_array($decoded) ? $decoded : ['status' => false, 'message' => 'Invalid server response'];
}
