<?php

/**
 * External License Manager — WHMCS Addon Module
 *
 * Bridges WHMCS product/service lifecycle events to the ELMS
 * License API. Configure the License Server URL, API key and secret
 * under Setup > Addon Modules.
 *
 * @package  ExternalLicenseManager
 * @author   ELMS
 * @version  1.0.0
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

/**
 * Module configuration + metadata.
 *
 * @return array<string,mixed>
 */
function external_license_manager_config()
{
    return [
        'name'        => 'External License Manager',
        'description' => 'Automatically generates and manages software licenses on an external ELMS License Server when WHMCS products are provisioned, suspended, unsuspended, or terminated.',
        'author'      => 'ELMS',
        'language'    => 'english',
        'version'     => '1.0.0',
        'fields'      => [
            'server_url' => [
                'FriendlyName' => 'License Server URL',
                'Type'         => 'text',
                'Size'         => '60',
                'Default'      => 'https://license.example.com',
                'Description'  => 'Base URL of your ELMS server (no trailing slash).',
            ],
            'api_key' => [
                'FriendlyName' => 'API Key',
                'Type'         => 'text',
                'Size'         => '60',
                'Description'  => 'Public API key from ELMS > API Keys.',
            ],
            'api_secret' => [
                'FriendlyName' => 'API Secret',
                'Type'         => 'password',
                'Size'         => '60',
                'Description'  => 'Secret key paired with the API key (used for HMAC signing).',
            ],
            'default_product_id' => [
                'FriendlyName' => 'Default ELMS Product ID',
                'Type'         => 'text',
                'Size'         => '6',
                'Description'  => 'Fallback ELMS product id when a WHMCS product has no mapping configured.',
            ],
        ],
    ];
}

/**
 * Activation: create a mapping table for WHMCS service -> ELMS license.
 *
 * @return array<string,string>
 */
function external_license_manager_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_elms_licenses')) {
            Capsule::schema()->create('mod_elms_licenses', function ($table) {
                $table->increments('id');
                $table->integer('service_id')->index();
                $table->string('license_key', 64)->nullable();
                $table->string('product_key', 80)->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamp('created_at')->nullable();
            });
        }
        return ['status' => 'success', 'description' => 'ELMS module activated.'];
    } catch (\Throwable $e) {
        return ['status' => 'error', 'description' => 'Activation failed: ' . $e->getMessage()];
    }
}

/**
 * Deactivation: keep data (do not drop mapping) to avoid data loss.
 *
 * @return array<string,string>
 */
function external_license_manager_deactivate()
{
    return ['status' => 'success', 'description' => 'ELMS module deactivated. License mappings retained.'];
}

/**
 * Admin area output: simple status overview.
 *
 * @param array<string,mixed> $vars
 */
function external_license_manager_output($vars)
{
    $count = 0;
    try {
        $count = Capsule::table('mod_elms_licenses')->count();
    } catch (\Throwable $e) {
        // ignore
    }

    echo '<div class="row"><div class="col-md-12">';
    echo '<h3>External License Manager</h3>';
    echo '<p>Connected server: <strong>' . htmlspecialchars($vars['server_url'] ?? 'not set') . '</strong></p>';
    echo '<p>Tracked licenses: <strong>' . (int) $count . '</strong></p>';
    echo '<p class="text-muted">Licenses are created and managed automatically via WHMCS product lifecycle hooks. '
       . 'Manage them from the ELMS admin panel.</p>';
    echo '</div></div>';
}

/**
 * Client area: expose license info for the customer's service.
 *
 * @param array<string,mixed> $vars
 * @return array<string,mixed>
 */
function external_license_manager_clientarea($vars)
{
    return [
        'pagetitle' => 'My Licenses',
        'breadcrumb' => ['index.php?m=external_license_manager' => 'My Licenses'],
        'templatefile' => 'clientarea',
        'requirelogin' => true,
        'vars' => [
            'licenses' => [],
        ],
    ];
}
