<?php

/**
 * External License Manager — WHMCS Hooks
 *
 * Maps WHMCS product lifecycle events to ELMS license actions.
 *   AfterModuleCreate     -> create license
 *   AfterModuleSuspend    -> suspend license
 *   AfterModuleUnsuspend  -> unsuspend (activate) license
 *   AfterModuleTerminate  -> terminate license
 *   DailyCronJob          -> housekeeping placeholder
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/ElmsApiClient.php';

/**
 * Resolve the ELMS product id for a given WHMCS service.
 * Falls back to the module's default_product_id setting.
 */
function elms_resolve_product_id(array $params): int
{
    // A WHMCS custom field or config option named "elms_product_id"
    // can override the mapping. Otherwise use the addon default.
    try {
        $settings = \WHMCS\Database\Capsule::table('tbladdonmodules')
            ->where('module', 'external_license_manager')
            ->pluck('value', 'setting')->toArray();
        $default = (int) ($settings['default_product_id'] ?? 0);
    } catch (\Throwable $e) {
        $default = 0;
    }

    foreach (($params['configoptions'] ?? []) as $k => $v) {
        if (strtolower((string) $k) === 'elms_product_id' && (int) $v > 0) {
            return (int) $v;
        }
    }
    return $default;
}

/**
 * Store/lookup the license key mapped to a WHMCS service.
 */
function elms_get_service_license(int $serviceId): ?string
{
    try {
        $row = \WHMCS\Database\Capsule::table('mod_elms_licenses')
            ->where('service_id', $serviceId)->first();
        return $row->license_key ?? null;
    } catch (\Throwable $e) {
        return null;
    }
}

function elms_store_service_license(int $serviceId, string $licenseKey, string $productKey): void
{
    try {
        \WHMCS\Database\Capsule::table('mod_elms_licenses')->updateOrInsert(
            ['service_id' => $serviceId],
            ['license_key' => $licenseKey, 'product_key' => $productKey, 'status' => 'active', 'created_at' => date('Y-m-d H:i:s')]
        );
    } catch (\Throwable $e) {
        logActivity('ELMS: failed to store license mapping - ' . $e->getMessage());
    }
}

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

    $expiry = $params['model']->nextduedate ?? null;

    $res = $client->post('/api/license/create', [
        'product_id'       => elms_resolve_product_id($params),
        'whmcs_service_id' => $serviceId,
        'customer_name'    => trim(($params['clientsdetails']['firstname'] ?? '') . ' ' . ($params['clientsdetails']['lastname'] ?? '')),
        'customer_email'   => $params['clientsdetails']['email'] ?? null,
        'domain'           => $params['domain'] ?? null,
        'expiry_date'      => $expiry,
        'activation_limit' => 1,
    ]);

    if (!empty($res['status']) && !empty($res['data']['license_key'])) {
        elms_store_service_license($serviceId, $res['data']['license_key'], $res['data']['product'] ?? '');
        logActivity('ELMS: license created for service #' . $serviceId . ' -> ' . $res['data']['license_key']);
    } else {
        logActivity('ELMS: license create failed for service #' . $serviceId . ' - ' . ($res['message'] ?? 'unknown'));
    }
});

add_hook('AfterModuleSuspend', 1, function (array $params) {
    elms_change_status($params, '/api/license/suspend', 'suspend');
});

add_hook('AfterModuleUnsuspend', 1, function (array $params) {
    elms_change_status($params, '/api/license/unsuspend', 'unsuspend');
});

add_hook('AfterModuleTerminate', 1, function (array $params) {
    elms_change_status($params, '/api/license/terminate', 'terminate');
});

/**
 * Shared status-change helper.
 */
function elms_change_status(array $params, string $path, string $label): void
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
    $res = $client->post($path, ['license_key' => $key]);
    logActivity('ELMS: ' . $label . ' service #' . $serviceId . ' -> ' . ($res['message'] ?? 'no response'));
}

add_hook('DailyCronJob', 1, function () {
    // Reserved for future expiry sync / reconciliation.
});
