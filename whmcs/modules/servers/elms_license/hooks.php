<?php

/**
 * ELMS License Server — WHMCS UI Cleanup Hooks
 *
 * Prevents WHMCS from displaying cPanel/Shared Hosting details
 * (Username, Password, Server IP, Nameservers, Control Panel login, Hosting Sidebar)
 * for Software License products in both Client Area and Admin Service Area.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;
use WHMCS\View\Menu\Item as MenuItem;

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
        // Remove the 'credentials' box added by custom hosting sidebar hooks
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
