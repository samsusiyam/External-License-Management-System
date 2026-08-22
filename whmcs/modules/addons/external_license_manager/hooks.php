<?php

/**
 * External License Manager — WHMCS Hooks
 *
 * Maps WHMCS product lifecycle events to ELMS license actions:
 *   - AfterModuleCreate     -> Smart create license & auto-fill WHMCS Custom Field
 *   - AfterModuleSuspend    -> Suspend license
 *   - AfterModuleUnsuspend  -> Unsuspend (activate) license
 *   - AfterModuleTerminate  -> Terminate license
 *   - AfterModuleRenew      -> Sync extended expiry date
 *   - ClientAreaPageView    -> Expose license data
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/ElmsApiClient.php';

/**
 * Smart product resolution for a WHMCS service.
 * Looks in:
 *   1. Custom Fields: 'elms_product_id', 'product_id', 'product_key', 'product'
 *   2. Configurable Options: 'elms_product_id', 'product_id'
 *   3. Stored Product Mapping Table / Settings
 *   4. WHMCS Package Name
 *   5. Default Addon Product ID
 *
 * @param array<string,mixed> $params
 * @return mixed (int|string)
 */
function elms_resolve_product_identifier(array $params): mixed
{
    // 1. Check Custom Fields
    $customFields = $params['customfields'] ?? [];
    foreach ($customFields as $k => $v) {
        $key = strtolower(trim((string) $k));
        if (in_array($key, ['elms_product_id', 'product_id', 'product_key', 'license_product'], true) && !empty($v)) {
            return is_numeric($v) ? (int) $v : (string) $v;
        }
    }

    // 2. Check Configurable Options
    $configOptions = $params['configoptions'] ?? [];
    foreach ($configOptions as $k => $v) {
        $key = strtolower(trim((string) $k));
        if (in_array($key, ['elms_product_id', 'product_id', 'product_key'], true) && !empty($v)) {
            return is_numeric($v) ? (int) $v : (string) $v;
        }
    }

    // 3. Check Addon Settings / Mapping
    $packageId = (int) ($params['packageid'] ?? ($params['pid'] ?? 0));
    try {
        $settings = Capsule::table('tbladdonmodules')
            ->where('module', 'external_license_manager')
            ->pluck('value', 'setting');
        $s = is_object($settings) ? $settings->toArray() : (array) $settings;

        // Specific product mapping e.g. mapping_pid_5 = 2
        if ($packageId > 0 && !empty($s['map_pid_' . $packageId])) {
            return (int) $s['map_pid_' . $packageId];
        }

        // Default Product ID setting
        if (!empty($s['default_product_id']) && (int) $s['default_product_id'] > 0) {
            return (int) $s['default_product_id'];
        }
    } catch (\Throwable $e) {
        // ignore
    }

    // 4. Check WHMCS Product/Package Name
    if (!empty($params['model']->product->name)) {
        return (string) $params['model']->product->name;
    }
    if (!empty($params['package'])) {
        return (string) $params['package'];
    }

    return 0; // ELMS API will smart-fallback to the active product
}

/**
 * Store/lookup the license key mapped to a WHMCS service.
 */
function elms_get_service_license(int $serviceId): ?string
{
    try {
        $row = Capsule::table('mod_elms_licenses')
            ->where('service_id', $serviceId)->first();
        return $row->license_key ?? null;
    } catch (\Throwable $e) {
        return null;
    }
}

function elms_store_service_license(int $serviceId, string $licenseKey, string $productKey, ?string $domain = null): void
{
    try {
        Capsule::table('mod_elms_licenses')->updateOrInsert(
            ['service_id' => $serviceId],
            [
                'license_key' => $licenseKey,
                'product_key' => $productKey,
                'status'      => 'active',
                'created_at'  => date('Y-m-d H:i:s'),
            ]
        );
    } catch (\Throwable $e) {
        logActivity('ELMS: failed to store license mapping - ' . $e->getMessage());
    }
}

/**
 * Auto-fill WHMCS Custom Field "License Key" for the service.
 */
function elms_sync_custom_field(int $serviceId, int $packageId, string $licenseKey): void
{
    if ($serviceId <= 0 || $licenseKey === '') {
        return;
    }

    try {
        // Find custom field named 'License Key', 'license_key', 'License', or 'LicenseKey'
        $field = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where(function ($q) use ($packageId) {
                $q->where('relid', $packageId)->orWhere('relid', 0);
            })
            ->where(function ($q) {
                $q->where('fieldname', 'LIKE', '%license%')
                  ->orWhere('fieldname', 'LIKE', '%License%')
                  ->orWhere('fieldname', 'License Key');
            })
            ->first();

        if ($field !== null) {
            Capsule::table('tblcustomfieldsvalues')->updateOrInsert(
                ['fieldid' => $field->id, 'relid' => $serviceId],
                ['value' => $licenseKey]
            );
        }
    } catch (\Throwable $e) {
        logActivity('ELMS: could not auto-fill custom field - ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
// Lifecycle Hooks
// ---------------------------------------------------------------------------

add_hook('AfterModuleCreate', 1, function (array $params) {
    $client = ElmsApiClient::fromSettings();
    if ($client === null) {
        return;
    }
    $serviceId = (int) ($params['serviceid'] ?? 0);
    if ($serviceId <= 0 || elms_get_service_license($serviceId) !== null) {
        return; // already provisioned
    }

    $packageId = (int) ($params['packageid'] ?? ($params['pid'] ?? 0));
    $expiry    = $params['model']->nextduedate ?? null;
    $domain    = $params['domain'] ?? null;
    $prodIdent = elms_resolve_product_identifier($params);

    $customerName = trim(($params['clientsdetails']['firstname'] ?? '') . ' ' . ($params['clientsdetails']['lastname'] ?? ''));
    $customerEmail = $params['clientsdetails']['email'] ?? null;

    $payload = [
        'whmcs_service_id' => $serviceId,
        'customer_name'    => $customerName ?: 'WHMCS Client #' . ($params['clientsdetails']['userid'] ?? $serviceId),
        'customer_email'   => $customerEmail,
        'domain'           => $domain,
        'expiry_date'      => $expiry,
        'activation_limit' => 1,
        'domain_lock'      => 1,
    ];

    if (is_numeric($prodIdent) && (int) $prodIdent > 0) {
        $payload['product_id'] = (int) $prodIdent;
    } elseif (is_string($prodIdent) && trim($prodIdent) !== '') {
        $payload['product'] = trim($prodIdent);
    }

    $res = $client->createLicense($payload);

    if (!empty($res['status']) && !empty($res['data']['license_key'])) {
        $licKey  = (string) $res['data']['license_key'];
        $prodKey = (string) ($res['data']['product'] ?? '');

        elms_store_service_license($serviceId, $licKey, $prodKey, $domain);
        elms_sync_custom_field($serviceId, $packageId, $licKey);

        logActivity('ELMS: license generated for service #' . $serviceId . ' [' . $licKey . ']');
    } else {
        logActivity('ELMS: license creation failed for service #' . $serviceId . ' - ' . ($res['message'] ?? 'unknown error'));
    }
});

add_hook('AfterModuleSuspend', 1, function (array $params) {
    elms_change_status($params, 'suspend');
});

add_hook('AfterModuleUnsuspend', 1, function (array $params) {
    elms_change_status($params, 'unsuspend');
});

add_hook('AfterModuleTerminate', 1, function (array $params) {
    elms_change_status($params, 'terminate');
});

add_hook('AfterModuleRenew', 1, function (array $params) {
    $client = ElmsApiClient::fromSettings();
    if ($client === null) {
        return;
    }
    $serviceId = (int) ($params['serviceid'] ?? 0);
    $key = elms_get_service_license($serviceId);
    if ($key === null) {
        return;
    }

    $newExpiry = $params['model']->nextduedate ?? null;
    if ($newExpiry) {
        $res = $client->renewLicense($key, $newExpiry);
        logActivity('ELMS: license #' . $key . ' renewed till ' . $newExpiry . ' (' . ($res['message'] ?? '') . ')');
    }
});

/**
 * Shared status-change helper.
 */
function elms_change_status(array $params, string $action): void
{
    $client = ElmsApiClient::fromSettings();
    if ($client === null) {
        return;
    }
    $serviceId = (int) ($params['serviceid'] ?? 0);
    $key = elms_get_service_license($serviceId);
    if ($key === null) {
        return;
    }

    $res = $client->changeStatus($key, $action);

    // Update local mapping table
    try {
        $statusMap = [
            'suspend'   => 'suspended',
            'unsuspend' => 'active',
            'terminate' => 'terminated',
        ];
        if (isset($statusMap[$action])) {
            Capsule::table('mod_elms_licenses')
                ->where('service_id', $serviceId)
                ->update(['status' => $statusMap[$action]]);
        }
    } catch (\Throwable $e) {
        // ignore
    }

    logActivity('ELMS: ' . ucfirst($action) . ' service #' . $serviceId . ' -> ' . ($res['message'] ?? 'no response'));
}
