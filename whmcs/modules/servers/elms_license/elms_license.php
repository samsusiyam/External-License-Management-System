<?php

/**
 * ELMS License Server — WHMCS Provisioning Server Module (cPanel-Style)
 *
 * Connect your ELMS License Server once globally under Setup > Products/Services > Servers
 * or via the Addon Module. In your products, simply select "ELMS License Server" and choose
 * your product from the dynamic dropdown!
 *
 * Features:
 * - One-time global API connection
 * - Dynamic product selection dropdown
 * - Automatic domain extraction from Custom Fields or WHMCS Domain input
 * - Automatic IP extraction from Custom Fields (only locks IP if provided)
 * - Real-time Expiry Date synchronization
 * - Clean UI without raw hosting credentials (username/password)
 *
 * @package    ELMS
 * @version    2.2.0
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
        'RequiresServer'    => true,
        'DefaultNonSSLPort' => '80',
        'DefaultSSLPort'    => '443',
    ];
}

/**
 * Dynamic Product Configuration in WHMCS:
 * Setup > Products/Services > Products/Services > Edit Product > Module Settings
 *
 * @param array<string,mixed> $params
 * @return array<string,mixed>
 */
function elms_license_ConfigOptions(array $params = [])
{
    $creds = elms_server_resolve_credentials($params);
    $productOptions = ['0' => 'Auto-Match by WHMCS Product Name'];

    // Fetch active products from ELMS Server to build dynamic dropdown
    if (!empty($creds['server_url'])) {
        try {
            $resp = elms_server_api_call($creds['server_url'], $creds['api_key'], $creds['api_secret'], '/api/products', []);
            if (!empty($resp['data']['products'])) {
                foreach ($resp['data']['products'] as $prod) {
                    $key = (string) ($prod['product_key'] ?: $prod['id']);
                    $productOptions[$key] = $prod['product_name'] . ' (' . $prod['product_key'] . ')';
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }
    }

    $productOptionsString = '';
    foreach ($productOptions as $k => $label) {
        $productOptionsString .= ($productOptionsString ? ',' : '') . $k . '|' . str_replace(',', ' ', $label);
    }

    return [
        'Select ELMS Product' => [
            'Type'        => 'dropdown',
            'Options'     => $productOptionsString,
            'Default'     => '0',
            'Description' => 'Choose which software product this WHMCS product issues licenses for.',
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
            'Options'     => 'Auto (if IP provided),Always,Never',
            'Default'     => 'Auto (if IP provided)',
            'Description' => 'Auto: Locks IP only if the customer provided an IP Address.',
        ],
        'License Server URL (Optional Override)' => [
            'Type'        => 'text',
            'Size'        => '60',
            'Description' => 'Leave blank to use global server from Setup > Servers or Addon.',
        ],
        'API Key (Optional Override)' => [
            'Type'        => 'text',
            'Size'        => '50',
            'Description' => 'Leave blank to use global API Key.',
        ],
        'API Secret (Optional Override)' => [
            'Type'        => 'password',
            'Size'        => '60',
            'Description' => 'Leave blank to use global API Secret.',
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
    $creds = elms_server_resolve_credentials($params);

    if (empty($creds['server_url'])) {
        return [
            'success' => false,
            'error'   => 'No License Server configured. Please configure under Setup > Servers or Addon Module.',
        ];
    }

    $res = elms_server_api_call($creds['server_url'], $creds['api_key'], $creds['api_secret'], '/api/license/verify', [
        'license_key' => 'TEST-PING-' . time(),
        'domain'      => 'ping.test',
    ]);

    if (isset($res['status'])) {
        return [
            'success' => true,
            'data'    => 'Connected successfully to ' . $creds['server_url'] . ' (API responding)',
        ];
    }

    return [
        'success' => false,
        'error'   => $res['message'] ?? 'Connection failed',
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
    $creds           = elms_server_resolve_credentials($params);
    $selectedProduct = trim((string) ($params['configoption1'] ?? '0'));
    $activationLimit = max(1, (int) ($params['configoption2'] ?? 1));
    $domainLockOpt   = $params['configoption3'] ?? 'Yes';
    $ipLockOpt       = $params['configoption4'] ?? 'Auto (if IP provided)';

    if (empty($creds['server_url'])) {
        return 'ELMS Error: License Server URL is not configured.';
    }

    $serviceId = (int) $params['serviceid'];
    
    // Normalize Expiry Date (YYYY-MM-DD or null for Lifetime)
    $rawExpiry = $params['nextduedate'] ?? ($params['model']->nextduedate ?? null);
    $expiry    = elms_format_whmcs_date($rawExpiry);

    // Extract Domain and IP from Custom Fields or standard WHMCS fields
    $domain = elms_extract_domain($params);
    $ip     = elms_extract_ip($params);

    $domainLock = ($domainLockOpt === 'Yes' && !empty($domain)) ? 1 : 0;
    
    $ipLock = 0;
    if ($ipLockOpt === 'Always') {
        $ipLock = 1;
    } elseif ($ipLockOpt === 'Auto (if IP provided)' && !empty($ip)) {
        $ipLock = 1;
    }

    $customerName  = trim(($params['clientsdetails']['firstname'] ?? '') . ' ' . ($params['clientsdetails']['lastname'] ?? ''));
    $customerEmail = $params['clientsdetails']['email'] ?? null;

    $payload = [
        'whmcs_service_id' => $serviceId,
        'customer_name'    => $customerName ?: 'Client #' . ($params['clientsdetails']['userid'] ?? $serviceId),
        'customer_email'   => $customerEmail,
        'domain'           => $domain ?: null,
        'ip_address'       => $ip ?: null,
        'expiry_date'      => $expiry,
        'activation_limit' => $activationLimit,
        'domain_lock'      => $domainLock,
        'ip_lock'          => $ipLock,
    ];

    if ($selectedProduct !== '0' && $selectedProduct !== '') {
        if (is_numeric($selectedProduct)) {
            $payload['product_id'] = (int) $selectedProduct;
        } else {
            $payload['product'] = $selectedProduct;
        }
    } else {
        $payload['product'] = $params['package'] ?? '';
    }

    $res = elms_server_api_call($creds['server_url'], $creds['api_key'], $creds['api_secret'], '/api/license/create', $payload);

    if (!empty($res['status']) && !empty($res['data']['license_key'])) {
        $licKey  = (string) $res['data']['license_key'];
        $prodKey = (string) ($res['data']['product'] ?? '');

        // Clear username & password in tblhosting so hosting sidebar hooks don't trigger
        Capsule::table('tblhosting')
            ->where('id', $serviceId)
            ->update([
                'username' => '',
                'password' => '',
                'domain'   => $domain ?: ($params['domain'] ?? ''),
            ]);

        // Track in mod_elms_licenses table
        try {
            if (!Capsule::schema()->hasTable('mod_elms_licenses')) {
                Capsule::schema()->create('mod_elms_licenses', function ($table) {
                    $table->increments('id');
                    $table->integer('service_id')->unique();
                    $table->string('license_key', 64)->nullable()->index();
                    $table->string('product_key', 80)->nullable();
                    $table->string('status', 20)->default('active');
                    $table->string('expiry_date', 30)->nullable();
                    $table->timestamp('created_at')->nullable();
                });
            }
            Capsule::table('mod_elms_licenses')->updateOrInsert(
                ['service_id' => $serviceId],
                [
                    'license_key'  => $licKey,
                    'product_key'  => $prodKey,
                    'status'       => 'active',
                    'expiry_date'  => $expiry,
                    'created_at'   => date('Y-m-d H:i:s')
                ]
            );
        } catch (\Throwable $e) {}

        // Auto-fill Custom Field named 'License Key' if exists
        elms_server_sync_custom_field($serviceId, (int) ($params['packageid'] ?? ($params['pid'] ?? 0)), $licKey);

        return 'success';
    }

    return 'ELMS Provisioning Failed: ' . ($res['message'] ?? 'Unknown Error');
}

/**
 * Suspend License.
 */
function elms_license_SuspendAccount(array $params)
{
    $creds  = elms_server_resolve_credentials($params);
    $licKey = elms_server_get_license_key($params);

    if ($licKey === '') {
        return 'No License Key assigned to this service.';
    }

    $res = elms_server_api_call($creds['server_url'], $creds['api_key'], $creds['api_secret'], '/api/license/suspend', [
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
 */
function elms_license_UnsuspendAccount(array $params)
{
    $creds  = elms_server_resolve_credentials($params);
    $licKey = elms_server_get_license_key($params);

    if ($licKey === '') {
        return 'No License Key assigned to this service.';
    }

    $res = elms_server_api_call($creds['server_url'], $creds['api_key'], $creds['api_secret'], '/api/license/unsuspend', [
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
 */
function elms_license_TerminateAccount(array $params)
{
    $creds  = elms_server_resolve_credentials($params);
    $licKey = elms_server_get_license_key($params);

    if ($licKey === '') {
        return 'success';
    }

    $res = elms_server_api_call($creds['server_url'], $creds['api_key'], $creds['api_secret'], '/api/license/terminate', [
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
 * Renew License (Extend Expiry on ELMS server).
 */
function elms_license_Renew(array $params)
{
    $creds     = elms_server_resolve_credentials($params);
    $licKey    = elms_server_get_license_key($params);
    $rawExpiry = $params['nextduedate'] ?? ($params['model']->nextduedate ?? null);
    $newExpiry = elms_format_whmcs_date($rawExpiry);

    if ($licKey === '') {
        return 'success';
    }

    $res = elms_server_api_call($creds['server_url'], $creds['api_key'], $creds['api_secret'], '/api/license/renew', [
        'license_key' => $licKey,
        'expiry_date' => $newExpiry,
    ]);

    if (!empty($res['status'])) {
        try {
            Capsule::table('mod_elms_licenses')->where('license_key', $licKey)->update(['expiry_date' => $newExpiry, 'status' => 'active']);
        } catch (\Throwable $e) {}
        return 'success';
    }

    return 'Renew Failed: ' . ($res['message'] ?? 'Unknown Error');
}

/**
 * Custom Admin Action Buttons on Service View.
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
    $creds  = elms_server_resolve_credentials($params);
    $licKey = elms_server_get_license_key($params);
    $domain = elms_extract_domain($params);

    if ($licKey === '') {
        return 'Error: No license key found for this service.';
    }

    $res = elms_server_api_call($creds['server_url'], $creds['api_key'], $creds['api_secret'], '/api/license/verify', [
        'license_key' => $licKey,
        'domain'      => $domain,
    ]);

    return !empty($res['status']) ? ('License Valid! Status: ' . ($res['data']['status'] ?? 'active') . ', Expiry: ' . ($res['data']['expiry'] ?? 'Never')) : ('License Error: ' . ($res['message'] ?? 'Invalid'));
}

function elms_license_ReissueLicenseKey(array $params)
{
    return elms_license_CreateAccount($params);
}

function elms_license_ResetLicenseBindings(array $params)
{
    $creds  = elms_server_resolve_credentials($params);
    $licKey = elms_server_get_license_key($params);

    if ($licKey === '') {
        return 'Error: No license key found.';
    }

    $res = elms_server_api_call($creds['server_url'], $creds['api_key'], $creds['api_secret'], '/api/license/reset', [
        'license_key' => $licKey,
    ]);

    return !empty($res['status']) ? 'Domain and IP bindings reset successfully.' : ('Reset Failed: ' . ($res['message'] ?? 'Error'));
}

/**
 * Display license details in WHMCS Admin Service page (clientsservices.php).
 */
function elms_license_AdminServicesTabFields(array $params)
{
    $licKey = elms_server_get_license_key($params);
    $domain = elms_extract_domain($params);
    $ip     = elms_extract_ip($params);

    $fields = [
        'License Key' => '<strong style="font-family:monospace; font-size:15px; color:#0f172a; background:#f8fafc; border:1px solid #cbd5e1; padding:4px 10px; border-radius:4px; letter-spacing:0.5px;">' . htmlspecialchars($licKey ?: 'Not Generated Yet') . '</strong>',
        'Bound Domain' => htmlspecialchars($domain ?: 'Any Domain'),
    ];

    if (!empty($ip)) {
        $fields['Bound IP Address'] = htmlspecialchars($ip);
    }

    return $fields;
}

/**
 * Display license key & details directly inside WHMCS Client Area Product Details page.
 */
function elms_license_ClientArea(array $params)
{
    $licKey = elms_server_get_license_key($params);
    $domain = elms_extract_domain($params);
    $ip     = elms_extract_ip($params);
    $creds  = elms_server_resolve_credentials($params);

    $rawExpiry = $params['nextduedate'] ?? ($params['model']->nextduedate ?? null);
    $expiryStr = elms_format_whmcs_date($rawExpiry) ?: 'Lifetime / Perpetual';
    $status    = $params['status'] ?? 'Active';

    // Live sync check with ELMS server
    if (!empty($licKey) && !empty($creds['server_url'])) {
        try {
            $check = elms_server_api_call($creds['server_url'], $creds['api_key'], $creds['api_secret'], '/api/license/verify', [
                'license_key' => $licKey,
                'domain'      => $domain ?: null,
            ]);
            if (!empty($check['data'])) {
                if (!empty($check['data']['expiry'])) {
                    $expiryStr = $check['data']['expiry'];
                }
                if (!empty($check['data']['status'])) {
                    $status = ucfirst((string) $check['data']['status']);
                }
            }
        } catch (\Throwable $e) {}
    }

    return [
        'tabOverviewReplacementTemplate' => 'clientarea.tpl',
        'templateVariables' => [
            'license_key'     => $licKey,
            'domain'          => $domain,
            'ip_address'      => $ip,
            'service_status'  => $status,
            'nextduedate'     => $expiryStr,
            'server_url'      => $creds['server_url'],
            'product_name'    => $params['package'] ?? 'Software License',
            'product_key'     => (string) ($params['configoption1'] ?? ($params['package'] ?? '')),
        ],
    ];
}

// ---------------------------------------------------------------------------
// Smart Helpers: Date Normalization, Credentials & Custom Field Resolvers
// ---------------------------------------------------------------------------

/**
 * Convert WHMCS date into YYYY-MM-DD or null (for Lifetime/0000-00-00).
 */
function elms_format_whmcs_date($date): ?string
{
    if (empty($date)) {
        return null;
    }
    if ($date instanceof \DateTimeInterface) {
        return $date->format('Y-m-d');
    }
    $str = trim((string) $date);
    if ($str === '' || $str === '0000-00-00' || $str === '00/00/0000' || str_starts_with($str, '0000')) {
        return null;
    }
    $ts = strtotime($str);
    return ($ts !== false && $ts > 0) ? date('Y-m-d', $ts) : null;
}

/**
 * Resolve server URL, API Key, and Secret.
 *
 * @param array<string,mixed> $params
 * @return array{server_url:string,api_key:string,api_secret:string}
 */
function elms_server_resolve_credentials(array $params = []): array
{
    // 1. Check Product-level overrides
    $url    = rtrim((string) ($params['configoption5'] ?? ''), '/');
    $key    = (string) ($params['configoption6'] ?? '');
    $secret = (string) ($params['configoption7'] ?? '');

    if (!empty($url) && !empty($key) && !empty($secret)) {
        return ['server_url' => $url, 'api_key' => $key, 'api_secret' => $secret];
    }

    // 2. Check WHMCS Server / Server Group attached to product
    if (!empty($params['serverhostname']) && !empty($params['serverusername']) && !empty($params['serverpassword'])) {
        $scheme = (!empty($params['serversecure']) || str_starts_with($params['serverhostname'], 'https://')) ? 'https' : 'http';
        $host = preg_replace('#^https?://#', '', $params['serverhostname']);
        $sUrl = $scheme . '://' . rtrim($host, '/');
        return [
            'server_url' => $url ?: $sUrl,
            'api_key'    => $key ?: (string) $params['serverusername'],
            'api_secret' => $secret ?: (string) $params['serverpassword'],
        ];
    }

    // 3. Check WHMCS Addon Module settings (tbladdonmodules)
    try {
        $addonSettings = Capsule::table('tbladdonmodules')
            ->where('module', 'external_license_manager')
            ->pluck('value', 'setting');
        $s = is_object($addonSettings) ? $addonSettings->toArray() : (array) $addonSettings;

        if (!empty($s['server_url'])) {
            return [
                'server_url' => $url ?: rtrim((string) $s['server_url'], '/'),
                'api_key'    => $key ?: (string) ($s['api_key'] ?? ''),
                'api_secret' => $secret ?: (string) ($s['api_secret'] ?? ''),
            ];
        }
    } catch (\Throwable $e) {}

    // 4. Check tblservers table for type = 'elms_license'
    try {
        $srv = Capsule::table('tblservers')->where('type', 'elms_license')->where('disabled', 0)->first();
        if ($srv) {
            $scheme = ($srv->secure || str_starts_with($srv->hostname, 'https://')) ? 'https' : 'http';
            $host = preg_replace('#^https?://#', '', $srv->hostname);
            $sUrl = $scheme . '://' . rtrim($host, '/');
            return [
                'server_url' => $url ?: $sUrl,
                'api_key'    => $key ?: (string) $srv->username,
                'api_secret' => $secret ?: (string) decrypt($srv->password),
            ];
        }
    } catch (\Throwable $e) {}

    return ['server_url' => $url, 'api_key' => $key, 'api_secret' => $secret];
}

/**
 * Extract Domain name.
 */
function elms_extract_domain(array $params): string
{
    $customFields = $params['customfields'] ?? [];
    foreach ($customFields as $k => $v) {
        $name = strtolower(trim(str_replace([' ', '_', '-'], '', (string) $k)));
        if (in_array($name, ['domain', 'domainname', 'website', 'url', 'host', 'site'], true) && !empty($v)) {
            return elms_clean_domain((string) $v);
        }
    }

    if (!empty($params['domain'])) {
        return elms_clean_domain((string) $params['domain']);
    }

    return '';
}

/**
 * Extract IP Address.
 */
function elms_extract_ip(array $params): string
{
    $customFields = $params['customfields'] ?? [];
    foreach ($customFields as $k => $v) {
        $name = strtolower(trim(str_replace([' ', '_', '-'], '', (string) $k)));
        if (in_array($name, ['ip', 'ipaddress', 'serverip', 'serveripaddress'], true) && !empty($v)) {
            return trim((string) $v);
        }
    }

    if (!empty($params['dedicatedip'])) {
        return trim((string) $params['dedicatedip']);
    }

    return '';
}

function elms_clean_domain(string $domain): string
{
    $d = strtolower(trim($domain));
    $d = preg_replace('#^https?://#', '', $d) ?? $d;
    $d = explode('/', $d)[0];
    return preg_replace('/^www\./', '', $d) ?? $d;
}

function elms_server_get_license_key(array $params): string
{
    $serviceId = (int) ($params['serviceid'] ?? ($params['id'] ?? 0));
    
    // 1. Check mod_elms_licenses table
    if ($serviceId > 0) {
        try {
            $row = Capsule::table('mod_elms_licenses')->where('service_id', $serviceId)->first();
            if ($row && !empty($row->license_key)) {
                return trim((string) $row->license_key);
            }
        } catch (\Throwable $e) {}
    }

    // 2. Check Custom Fields
    $customFields = $params['customfields'] ?? [];
    foreach ($customFields as $k => $v) {
        if (stripos((string) $k, 'license') !== false && !empty($v)) {
            return trim((string) $v);
        }
    }

    // 3. Fallback to password/username for legacy records
    if (!empty($params['password'])) {
        $decrypted = decrypt($params['password']);
        if (!empty($decrypted)) {
            return trim((string) $decrypted);
        }
    }
    if (!empty($params['username'])) {
        return trim((string) $params['username']);
    }

    return '';
}

function elms_server_sync_custom_field(int $serviceId, int $packageId, string $licenseKey): void
{
    if ($serviceId <= 0 || $licenseKey === '') {
        return;
    }
    try {
        $field = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where(function ($q) use ($packageId) {
                $q->where('relid', $packageId)->orWhere('relid', 0);
            })
            ->where(function ($q) {
                $q->where('fieldname', 'LIKE', '%license%')
                  ->orWhere('fieldname', 'LIKE', '%License%');
            })
            ->first();

        if ($field !== null) {
            Capsule::table('tblcustomfieldsvalues')->updateOrInsert(
                ['fieldid' => $field->id, 'relid' => $serviceId],
                ['value' => $licenseKey]
            );
        }
    } catch (\Throwable $e) {}
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
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 ELMS-WHMCS-Provisioner/2.2',
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
