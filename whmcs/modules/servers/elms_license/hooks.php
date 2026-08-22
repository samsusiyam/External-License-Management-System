<?php

/**
 * ELMS License Server — WHMCS UI Cleanup Hooks
 *
 * Prevents WHMCS from displaying cPanel/Shared Hosting details
 * (Username, Password, Server IP, Nameservers) for Software License products.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

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
        // Strip out hosting credentials so WHMCS themes hide "Hosting Account Details"
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
