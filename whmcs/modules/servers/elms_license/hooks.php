<?php

/**
 * ELMS License Server — WHMCS UI Cleanup & Email Hooks
 *
 * Prevents WHMCS from displaying cPanel/Shared Hosting details
 * (Username, Password, Server IP, Nameservers, Control Panel login, Hosting Sidebar)
 * for Software License products, and provides automated Dedicated Software License
 * Welcome Email templates and merge fields.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;
use WHMCS\View\Menu\Item as MenuItem;

// Ensure email template exists on admin and client load
elms_ensure_software_license_email_template();

if (!function_exists('elms_ensure_software_license_email_template')) {
    function elms_ensure_software_license_email_template()
    {
        try {
            $exists = Capsule::table('tblemailtemplates')
                ->where('type', 'product')
                ->where('name', 'Software License Welcome Email')
                ->exists();

            $htmlMsg = '<p>Dear {$client_name},</p>' . "\n"
                . '<p>Thank you for your order! Your software license is now active and ready for use.</p>' . "\n"
                . '<div style="margin: 20px 0; padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-family: sans-serif;">' . "\n"
                . '  <h3 style="margin-top: 0; color: #1e293b; font-size: 18px;">License Details</h3>' . "\n"
                . '  <table style="width: 100%; border-collapse: collapse;">' . "\n"
                . '    <tr><td style="padding: 8px 0; color: #64748b; width: 140px;"><strong>Product:</strong></td><td style="padding: 8px 0; color: #0f172a;"><strong>{$service_product_name}</strong></td></tr>' . "\n"
                . '    <tr><td style="padding: 8px 0; color: #64748b;"><strong>License Key:</strong></td><td style="padding: 8px 0;"><span style="display:inline-block; font-family: monospace; font-size: 16px; font-weight: bold; background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 4px; border: 1px solid #bae6fd;">{$service_license_key|default:$service_password|default:$license_key}</span></td></tr>' . "\n"
                . '    <tr><td style="padding: 8px 0; color: #64748b;"><strong>Registered Domain:</strong></td><td style="padding: 8px 0; color: #0f172a;">{$service_domain}</td></tr>' . "\n"
                . '    <tr><td style="padding: 8px 0; color: #64748b;"><strong>Expiry / Due Date:</strong></td><td style="padding: 8px 0; color: #0f172a;">{$service_next_due_date}</td></tr>' . "\n"
                . '    <tr><td style="padding: 8px 0; color: #64748b;"><strong>Status:</strong></td><td style="padding: 8px 0; color: #16a34a; font-weight: bold;">Active</td></tr>' . "\n"
                . '  </table>' . "\n"
                . '</div>' . "\n"
                . '<p>You can view and manage your license anytime from your client portal:</p>' . "\n"
                . '<p><a href="{$service_link}" style="display: inline-block; background: #0284c7; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold;">View License in Client Area</a></p>' . "\n"
                . '<p>If you have any questions or require assistance, please feel free to open a support ticket.</p>' . "\n"
                . '<p>Best regards,<br>{$company_name}</p>';

            if (!$exists) {
                Capsule::table('tblemailtemplates')->insert([
                    'type'             => 'product',
                    'name'             => 'Software License Welcome Email',
                    'subject'          => 'Your Software License Details - {$service_product_name}',
                    'message'          => $htmlMsg,
                    'fromname'         => '',
                    'fromemail'        => '',
                    'disabled'         => 0,
                    'custom'           => 1,
                    'language'         => '',
                    'copyto'           => '',
                    'blindcopyto'      => '',
                    'plaintext'        => 0,
                ]);
            } else {
                Capsule::table('tblemailtemplates')
                    ->where('type', 'product')
                    ->where('name', 'Software License Welcome Email')
                    ->update(['message' => $htmlMsg]);
            }
        } catch (\Throwable $e) {}
    }
}

/**
 * EmailPreSend: Inject License Key, Domain, Product Name & Merge Fields into emails.
 */
add_hook('EmailPreSend', 1, function ($vars) {
    $serviceId = (int) ($vars['relid'] ?? ($vars['mergefields']['service_id'] ?? ($vars['mergefields']['id'] ?? 0)));
    if ($serviceId <= 0) {
        return [];
    }

    try {
        $service = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->where('tblhosting.id', $serviceId)
            ->select('tblproducts.servertype', 'tblproducts.name as product_name', 'tblhosting.domain', 'tblhosting.nextduedate', 'tblhosting.password', 'tblhosting.username')
            ->first();

        if ($service && $service->servertype === 'elms_license') {
            $licKey = '';

            // 1. mod_elms_licenses table
            $licRow = Capsule::table('mod_elms_licenses')->where('service_id', $serviceId)->first();
            if ($licRow && !empty($licRow->license_key)) {
                $licKey = trim((string) $licRow->license_key);
            }

            // 2. Custom Fields
            if ($licKey === '') {
                $cf = Capsule::table('tblcustomfields')
                    ->join('tblcustomfieldsvalues', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
                    ->where('tblcustomfieldsvalues.relid', $serviceId)
                    ->where(function ($q) {
                        $q->where('tblcustomfields.fieldname', 'LIKE', '%license%')
                          ->orWhere('tblcustomfields.fieldname', 'LIKE', '%key%');
                    })
                    ->value('tblcustomfieldsvalues.value');
                if (!empty($cf)) {
                    $licKey = trim((string) $cf);
                }
            }

            // 3. Encrypted password in tblhosting
            if ($licKey === '' && !empty($service->password)) {
                $dec = decrypt($service->password);
                if (!empty($dec)) {
                    $licKey = trim((string) $dec);
                }
            }
            if ($licKey === '' && !empty($service->username)) {
                $licKey = trim((string) $service->username);
            }

            $domain = $service->domain ?: 'Any Domain (Unrestricted)';
            $dueDate = ($service->nextduedate && $service->nextduedate !== '0000-00-00') ? $service->nextduedate : 'Lifetime / Perpetual';
            $systemUrl = Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value') ?: '';
            $serviceLink = rtrim($systemUrl, '/') . '/clientarea.php?action=productdetails&id=' . $serviceId;

            return [
                'mergefields' => [
                    'service_license_key'  => $licKey ?: 'Active (View in Client Area)',
                    'license_key'          => $licKey ?: 'Active (View in Client Area)',
                    'service_password'     => $licKey,
                    'password'             => $licKey,
                    'service_domain'       => $domain,
                    'service_product_name' => $service->product_name,
                    'service_next_due_date'=> $dueDate,
                    'service_link'         => $serviceLink,
                ],
            ];
        }
    } catch (\Throwable $e) {}

    return [];
});

/**
 * Client Area: Clear redundant hosting credentials variables.
 */
add_hook('ClientAreaPageProductDetails', 1, function ($vars) {
    $serviceId = (int) ($vars['id'] ?? ($vars['serviceid'] ?? 0));
    if ($serviceId <= 0) {
        return;
    }

    $isLicenseProduct = false;
    $serverType = $vars['servertype'] ?? ($vars['module'] ?? '');

    if ($serverType === 'elms_license') {
        $isLicenseProduct = true;
    } else {
        try {
            $product = Capsule::table('tblhosting')
                ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
                ->where('tblhosting.id', $serviceId)
                ->select('tblproducts.servertype')
                ->first();

            if ($product && $product->servertype === 'elms_license') {
                $isLicenseProduct = true;
            }
        } catch (\Throwable $e) {}
    }

    if ($isLicenseProduct) {
        return [
            'username'            => '',
            'password'            => '',
            'serverdata'          => null,
            'serverip'            => '',
            'hostname'            => '',
            'ns1'                 => '',
            'ns2'                 => '',
            'ns3'                 => '',
            'ns4'                 => '',
            'displayAccountLogin' => false,
            'showAccountDetails'  => false,
        ];
    }
});

/**
 * Client Area Secondary Sidebar: Remove any "Hosting Account Details" panel
 * if the current product is an ELMS License.
 */
add_hook('ClientAreaSecondarySidebar', 100, function (MenuItem $secondarySidebar) {
    $service = Menu::context('service');
    if (!$service) {
        return;
    }

    $isLicense = false;
    try {
        $product = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->where('tblhosting.id', $service->id)
            ->select('tblproducts.servertype')
            ->first();

        if ($product && $product->servertype === 'elms_license') {
            $isLicense = true;
        }
    } catch (\Throwable $e) {}

    if ($isLicense) {
        if (!is_null($secondarySidebar->getChild('credentials'))) {
            $secondarySidebar->removeChild('credentials');
        }
        if (!is_null($secondarySidebar->getChild('Hosting Account Details'))) {
            $secondarySidebar->removeChild('Hosting Account Details');
        }
    }
});

/**
 * Client Area Head: Hide default hosting details cards/tabs.
 */
add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    if (($vars['filename'] ?? '') !== 'clientarea' || ($vars['action'] ?? '') !== 'productdetails') {
        return '';
    }

    $serviceId = (int) ($vars['id'] ?? ($vars['serviceid'] ?? 0));
    if ($serviceId <= 0) {
        return '';
    }

    try {
        $product = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->where('tblhosting.id', $serviceId)
            ->select('tblproducts.servertype')
            ->first();

        if ($product && $product->servertype === 'elms_license') {
            return <<<HTML
<style>
/* Hide raw cPanel/Hosting credentials boxes on license product details */
.product-details .login-to-control-panel,
.product-details .cpanel-login,
.product-details #tabOverview .row .col-md-6:has(.fa-user),
.product-details #tabOverview .row .col-md-6:has(.fa-lock),
.product-details #tabOverview .row .col-md-6:has(.fa-server),
.hosting-card {
    display: none !important;
}
</style>
HTML;
        }
    } catch (\Throwable $e) {}

    return '';
});

/**
 * Admin Area Footer: Hide raw "Username" and "Password" input rows
 * on clientsservices.php for ELMS License products.
 */
add_hook('AdminAreaFooterOutput', 1, function ($vars) {
    if (($vars['filename'] ?? '') !== 'clientsservices') {
        return '';
    }

    return <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function() {
    var isElms = document.body.innerHTML.indexOf('elms_license') !== -1 || document.body.innerHTML.indexOf('License Key') !== -1;

    if (isElms) {
        var userInp = document.querySelector('input[name="username"]');
        if (userInp) {
            var trUser = userInp.closest('tr');
            if (trUser) trUser.style.display = 'none';
        }

        var passInp = document.querySelector('input[name="password"]');
        if (passInp) {
            var trPass = passInp.closest('tr');
            if (trPass) trPass.style.display = 'none';
        }
    }
});
</script>
HTML;
});
