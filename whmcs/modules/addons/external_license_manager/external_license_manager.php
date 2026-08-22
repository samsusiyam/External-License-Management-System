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
 * @version  2.0.0
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/ElmsApiClient.php';

/**
 * Module configuration + metadata.
 *
 * @return array<string,mixed>
 */
function external_license_manager_config()
{
    return [
        'name'        => 'External License Manager',
        'description' => 'Automated, zero-hassle license generation and lifecycle management connected to your external ELMS license server.',
        'author'      => 'ELMS',
        'language'    => 'english',
        'version'     => '2.0.0',
        'fields'      => [
            'server_url' => [
                'FriendlyName' => 'License Server URL',
                'Type'         => 'text',
                'Size'         => '60',
                'Default'      => 'https://lic.yourdomain.com',
                'Description'  => 'Base URL of your ELMS server (without trailing slash).',
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
                'Description'  => 'Secret key paired with the API key (used for HMAC-SHA256 signing).',
            ],
            'default_product_id' => [
                'FriendlyName' => 'Default ELMS Product ID / Key',
                'Type'         => 'text',
                'Size'         => '30',
                'Description'  => 'Fallback product ID or Key (Leave blank to auto-detect active product).',
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
                $table->integer('service_id')->unique();
                $table->string('license_key', 64)->nullable()->index();
                $table->string('product_key', 80)->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamp('created_at')->nullable();
            });
        }
        return ['status' => 'success', 'description' => 'ELMS module activated successfully.'];
    } catch (\Throwable $e) {
        return ['status' => 'error', 'description' => 'Activation failed: ' . $e->getMessage()];
    }
}

/**
 * Deactivation: keep data to avoid data loss.
 *
 * @return array<string,string>
 */
function external_license_manager_deactivate()
{
    return ['status' => 'success', 'description' => 'ELMS module deactivated. License records retained.'];
}

/**
 * Admin Area Dashboard & Control Center
 *
 * @param array<string,mixed> $vars
 */
function external_license_manager_output($vars)
{
    $modulelink = $vars['modulelink'];
    $client = ElmsApiClient::fromSettings();
    $alertMessage = '';
    $alertType = 'info';

    // -----------------------------------------------------------------------
    // Handle Form Actions
    // -----------------------------------------------------------------------
    $action = $_POST['elms_action'] ?? ($_GET['elms_action'] ?? '');

    if ($action === 'test_connection') {
        if ($client === null) {
            $alertMessage = 'License server credentials are not fully configured yet. Please check Addon Module settings.';
            $alertType = 'danger';
        } else {
            $res = $client->testConnection();
            if (!empty($res['status'])) {
                $alertMessage = '<strong>Success!</strong> ' . htmlspecialchars($res['message']) . ' (Latency: ' . $res['latency_ms'] . ' ms)';
                $alertType = 'success';
            } else {
                $alertMessage = '<strong>Connection Error:</strong> ' . htmlspecialchars($res['message']);
                $alertType = 'danger';
            }
        }
    } elseif ($action === 'setup_custom_fields') {
        // Auto-create "License Key" custom field on all products
        try {
            $products = Capsule::table('tblproducts')->get();
            $createdCount = 0;
            foreach ($products as $p) {
                $exists = Capsule::table('tblcustomfields')
                    ->where('type', 'product')
                    ->where('relid', $p->id)
                    ->where(function ($q) {
                        $q->where('fieldname', 'LIKE', '%license%')
                          ->orWhere('fieldname', 'LIKE', '%License%');
                    })->count();

                if ($exists === 0) {
                    Capsule::table('tblcustomfields')->insert([
                        'type'        => 'product',
                        'relid'       => $p->id,
                        'fieldname'   => 'License Key',
                        'fieldtype'   => 'text',
                        'description' => 'Software License Key automatically assigned by ELMS',
                        'adminonly'   => '',
                        'showorder'   => '',
                        'showinvoice' => 'on',
                    ]);
                    $createdCount++;
                }
            }
            $alertMessage = 'Custom Field setup complete. Added "License Key" custom field to ' . $createdCount . ' products.';
            $alertType = 'success';
        } catch (\Throwable $e) {
            $alertMessage = 'Error setting up custom fields: ' . $e->getMessage();
            $alertType = 'danger';
        }
    } elseif ($action === 'license_action' && !empty($_POST['license_key'])) {
        $licKey = trim($_POST['license_key']);
        $subAct = trim($_POST['sub_action']);
        if ($client !== null) {
            if ($subAct === 'verify') {
                $res = $client->verifyLicense($licKey);
                $alertMessage = 'Verify Result for ' . htmlspecialchars($licKey) . ': ' . ($res['message'] ?? 'Unknown');
                $alertType = !empty($res['status']) ? 'success' : 'warning';
            } elseif (in_array($subAct, ['suspend', 'unsuspend', 'terminate'], true)) {
                $res = $client->changeStatus($licKey, $subAct);
                $alertMessage = ucfirst($subAct) . ' Result: ' . ($res['message'] ?? 'Done');
                $alertType = !empty($res['status']) ? 'success' : 'warning';

                // Update local table status
                $statusMap = ['suspend' => 'suspended', 'unsuspend' => 'active', 'terminate' => 'terminated'];
                if (isset($statusMap[$subAct])) {
                    Capsule::table('mod_elms_licenses')->where('license_key', $licKey)->update(['status' => $statusMap[$subAct]]);
                }
            } elseif ($subAct === 'reset') {
                $res = $client->resetLicense($licKey);
                $alertMessage = 'Reset Result: ' . ($res['message'] ?? 'Done');
                $alertType = !empty($res['status']) ? 'success' : 'warning';
            }
        }
    } elseif ($action === 'manual_create' && !empty($_POST['service_id'])) {
        $svcId = (int) $_POST['service_id'];
        $svc = Capsule::table('tblhosting')->where('id', $svcId)->first();
        if ($svc && $client) {
            $clientData = Capsule::table('tblclients')->where('id', $svc->userid)->first();
            $cName = trim(($clientData->firstname ?? '') . ' ' . ($clientData->lastname ?? ''));
            $res = $client->createLicense([
                'whmcs_service_id' => $svcId,
                'customer_name'    => $cName ?: 'Client #' . $svc->userid,
                'customer_email'   => $clientData->email ?? null,
                'domain'           => $svc->domain ?? null,
                'expiry_date'      => $svc->nextduedate ?? null,
            ]);

            if (!empty($res['status']) && !empty($res['data']['license_key'])) {
                $licKey = (string) $res['data']['license_key'];
                $prodKey = (string) ($res['data']['product'] ?? '');
                Capsule::table('mod_elms_licenses')->updateOrInsert(
                    ['service_id' => $svcId],
                    ['license_key' => $licKey, 'product_key' => $prodKey, 'status' => 'active', 'created_at' => date('Y-m-d H:i:s')]
                );
                $alertMessage = 'License created successfully for Service #' . $svcId . ' -> <strong>' . htmlspecialchars($licKey) . '</strong>';
                $alertType = 'success';
            } else {
                $alertMessage = 'License creation failed: ' . ($res['message'] ?? 'unknown error');
                $alertType = 'danger';
            }
        } else {
            $alertMessage = 'Invalid Service ID #' . $svcId;
            $alertType = 'danger';
        }
    }

    // -----------------------------------------------------------------------
    // Fetch Statistics & Licenses
    // -----------------------------------------------------------------------
    $stats = [
        'total'     => 0,
        'active'    => 0,
        'suspended' => 0,
    ];
    $licenses = [];

    try {
        $stats['total']     = Capsule::table('mod_elms_licenses')->count();
        $stats['active']    = Capsule::table('mod_elms_licenses')->where('status', 'active')->count();
        $stats['suspended'] = Capsule::table('mod_elms_licenses')->where('status', 'suspended')->count();

        $licenses = Capsule::table('mod_elms_licenses as mel')
            ->leftJoin('tblhosting as h', 'h.id', '=', 'mel.service_id')
            ->leftJoin('tblproducts as p', 'p.id', '=', 'h.packageid')
            ->leftJoin('tblclients as c', 'c.id', '=', 'h.userid')
            ->select(
                'mel.*',
                'h.domain as service_domain',
                'h.nextduedate',
                'h.domainstatus as service_status',
                'p.name as product_name',
                'c.id as client_id',
                'c.firstname',
                'c.lastname',
                'c.email'
            )
            ->orderBy('mel.id', 'DESC')
            ->limit(50)
            ->get();
    } catch (\Throwable $e) {
        // table might not exist yet
    }

    $isConfigured = !empty($vars['server_url']) && !empty($vars['api_key']) && !empty($vars['api_secret']);
    ?>

    <style>
        .elms-card { background:#fff; border-radius:8px; border:1px solid #e2e8f0; padding:20px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
        .elms-stat-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:15px; text-align:center; }
        .elms-stat-val { font-size:24px; font-weight:700; color:#1e293b; }
        .elms-stat-lbl { font-size:12px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; }
        .elms-badge-active { background:#dcfce7; color:#15803d; padding:4px 8px; border-radius:4px; font-weight:600; font-size:12px; }
        .elms-badge-suspended { background:#fee2e2; color:#b91c1c; padding:4px 8px; border-radius:4px; font-weight:600; font-size:12px; }
        .elms-key { font-family:monospace; background:#f1f5f9; padding:3px 6px; border-radius:4px; font-size:13px; font-weight:bold; color:#0f172a; }
    </style>

    <div class="row" style="margin-top:10px;">
        <div class="col-md-12">
            <!-- Header Card -->
            <div class="elms-card">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h2 style="margin:0; font-size:22px; font-weight:700; color:#0f172a;">
                            <i class="fas fa-shield-alt" style="color:#2563eb; margin-right:8px;"></i>External License Manager
                        </h2>
                        <p style="margin:5px 0 0 0; color:#64748b;">
                            License Server: <strong><?= htmlspecialchars($vars['server_url'] ?? 'Not Configured') ?></strong>
                            <?php if ($isConfigured): ?>
                                <span class="label label-success" style="margin-left:8px;">Configured</span>
                            <?php else: ?>
                                <span class="label label-danger" style="margin-left:8px;">Needs Setup</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <form method="post" action="<?= $modulelink ?>" style="margin:0;">
                            <input type="hidden" name="elms_action" value="test_connection">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-plug"></i> Test API Connection
                            </button>
                        </form>
                        <form method="post" action="<?= $modulelink ?>" style="margin:0;">
                            <input type="hidden" name="elms_action" value="setup_custom_fields">
                            <button type="submit" class="btn btn-default btn-sm" onclick="return confirm('Ensure License Key custom field exists on all products?');">
                                <i class="fas fa-magic"></i> Auto-Setup Custom Fields
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (!empty($alertMessage)): ?>
                <div class="alert alert-<?= $alertType ?>" style="margin-bottom:20px;">
                    <?= $alertMessage ?>
                </div>
            <?php endif; ?>

            <!-- Stats Bar -->
            <div class="row">
                <div class="col-md-4">
                    <div class="elms-stat-box">
                        <div class="elms-stat-val"><?= (int) $stats['total'] ?></div>
                        <div class="elms-stat-lbl">Tracked Licenses</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="elms-stat-box">
                        <div class="elms-stat-val" style="color:#16a34a;"><?= (int) $stats['active'] ?></div>
                        <div class="elms-stat-lbl">Active Licenses</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="elms-stat-box">
                        <div class="elms-stat-val" style="color:#dc2626;"><?= (int) $stats['suspended'] ?></div>
                        <div class="elms-stat-lbl">Suspended / Inactive</div>
                    </div>
                </div>
            </div>

            <!-- Main Tabs -->
            <div class="elms-card" style="margin-top:20px;">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#tab-licenses" aria-controls="tab-licenses" role="tab" data-toggle="tab">
                            <i class="fas fa-key"></i> Issued Licenses (<?= count($licenses) ?>)
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tab-manual" aria-controls="tab-manual" role="tab" data-toggle="tab">
                            <i class="fas fa-plus-circle"></i> Manual License Tool
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#tab-guide" aria-controls="tab-guide" role="tab" data-toggle="tab">
                            <i class="fas fa-question-circle"></i> Quick Guide
                        </a>
                    </li>
                </ul>

                <div class="tab-content" style="padding-top:20px;">
                    <!-- Tab 1: Licenses Table -->
                    <div role="tabpanel" class="tab-pane active" id="tab-licenses">
                        <?php if (empty($licenses) || count($licenses) === 0): ?>
                            <div class="text-center" style="padding:40px; color:#64748b;">
                                <i class="fas fa-inbox" style="font-size:36px; margin-bottom:10px; color:#94a3b8;"></i>
                                <p>No licenses tracked yet. Licenses will be automatically generated when customers order software products.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Service ID</th>
                                            <th>Client</th>
                                            <th>Product</th>
                                            <th>License Key</th>
                                            <th>Domain</th>
                                            <th>Next Due Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($licenses as $lic): ?>
                                            <tr>
                                                <td>
                                                    <a href="clientsservices.php?id=<?= (int) $lic->service_id ?>" target="_blank">
                                                        #<?= (int) $lic->service_id ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="clientssummary.php?userid=<?= (int) $lic->client_id ?>" target="_blank">
                                                        <?= htmlspecialchars(($lic->firstname ?? '') . ' ' . ($lic->lastname ?? '')) ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($lic->product_name ?? 'Custom Product') ?></td>
                                                <td><span class="elms-key"><?= htmlspecialchars($lic->license_key ?? 'N/A') ?></span></td>
                                                <td><?= htmlspecialchars($lic->service_domain ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($lic->nextduedate ?? 'N/A') ?></td>
                                                <td>
                                                    <?php if ($lic->status === 'active'): ?>
                                                        <span class="elms-badge-active">Active</span>
                                                    <?php else: ?>
                                                        <span class="elms-badge-suspended"><?= htmlspecialchars(ucfirst($lic->status)) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="post" action="<?= $modulelink ?>" style="display:inline-block; margin:0;">
                                                        <input type="hidden" name="elms_action" value="license_action">
                                                        <input type="hidden" name="license_key" value="<?= htmlspecialchars($lic->license_key ?? '') ?>">
                                                        
                                                        <div class="btn-group btn-group-xs">
                                                            <button type="submit" name="sub_action" value="verify" class="btn btn-default btn-xs" title="Verify on Server">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <?php if ($lic->status === 'active'): ?>
                                                                <button type="submit" name="sub_action" value="suspend" class="btn btn-warning btn-xs" title="Suspend" onclick="return confirm('Suspend this license?');">
                                                                    <i class="fas fa-pause"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="submit" name="sub_action" value="unsuspend" class="btn btn-success btn-xs" title="Unsuspend">
                                                                    <i class="fas fa-play"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="submit" name="sub_action" value="reset" class="btn btn-info btn-xs" title="Reset Domain/IP Bindings" onclick="return confirm('Reset bindings for this license?');">
                                                                <i class="fas fa-sync"></i>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 2: Manual Generator -->
                    <div role="tabpanel" class="tab-pane" id="tab-manual">
                        <div style="max-width:600px;">
                            <h4>Generate License for Existing Service</h4>
                            <p class="text-muted">If a customer has an existing service without a license key, enter the Service ID below to provision one immediately:</p>
                            <form method="post" action="<?= $modulelink ?>">
                                <input type="hidden" name="elms_action" value="manual_create">
                                <div class="form-group">
                                    <label>WHMCS Service ID:</label>
                                    <input type="number" name="service_id" class="form-control" placeholder="e.g. 12" required>
                                    <span class="help-block">Find the ID in Clients > Products/Services (id=...).</span>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-key"></i> Generate License Now
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Tab 3: Quick Guide -->
                    <div role="tabpanel" class="tab-pane" id="tab-guide">
                        <div class="row">
                            <div class="col-md-6">
                                <h4>How it works:</h4>
                                <ol style="line-height:2;">
                                    <li>When an order is accepted/paid, WHMCS fires the <code>AfterModuleCreate</code> hook.</li>
                                    <li>The module contacts your ELMS License Server and creates a license key.</li>
                                    <li>The license key is automatically saved in WHMCS and shown in the customer's service details.</li>
                                    <li>If the service is suspended, unsuspended, or terminated, the license status updates automatically in real-time.</li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h4>How to use with your software:</h4>
                                <p>Include the <code>sdk/php/license.php</code> file inside your software and call:</p>
                                <pre style="background:#f8fafc; font-size:12px; padding:10px;">$elms = new ElmsLicense([
    'server'  => '<?= htmlspecialchars($vars['server_url'] ?? 'https://lic.yourdomain.com') ?>',
    'api_key' => '<?= htmlspecialchars(substr($vars['api_key'] ?? 'elms_pk_...', 0, 16)) ?>...',
    'secret'  => 'YOUR_API_SECRET',
]);
$check = $elms->verify($licenseKey);
if ($check['status']) {
    // License valid!
}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Client Area Output
 *
 * @param array<string,mixed> $vars
 * @return array<string,mixed>
 */
function external_license_manager_clientarea($vars)
{
    $userId = (int) ($_SESSION['uid'] ?? 0);
    $licenses = [];

    if ($userId > 0) {
        try {
            $licenses = Capsule::table('mod_elms_licenses as mel')
                ->join('tblhosting as h', 'h.id', '=', 'mel.service_id')
                ->leftJoin('tblproducts as p', 'p.id', '=', 'h.packageid')
                ->where('h.userid', $userId)
                ->select(
                    'mel.license_key',
                    'mel.product_key',
                    'mel.status',
                    'mel.created_at',
                    'h.id as service_id',
                    'h.domain',
                    'h.nextduedate',
                    'p.name as product_name'
                )
                ->orderBy('mel.id', 'DESC')
                ->get();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    return [
        'pagetitle'    => 'My Software Licenses',
        'breadcrumb'   => ['index.php?m=external_license_manager' => 'My Licenses'],
        'templatefile' => 'clientarea',
        'requirelogin' => true,
        'vars'         => [
            'licenses' => is_object($licenses) ? json_decode(json_encode($licenses), true) : (array) $licenses,
        ],
    ];
}
