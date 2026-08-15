<?php

namespace Elms\License;

require_once __DIR__ . '/../../php/license.php';

use ElmsLicense;

/**
 * ElmsLicenseManager
 *
 * Thin Laravel-friendly wrapper around the shared ElmsLicense SDK.
 * Resolve via the container: app('elms.license') or dependency injection.
 */
class ElmsLicenseManager
{
    private ElmsLicense $client;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config)
    {
        $this->client = new ElmsLicense([
            'server'    => $config['server'] ?? '',
            'api_key'   => $config['api_key'] ?? '',
            'secret'    => $config['secret'] ?? '',
            'product'   => $config['product'] ?? '',
            'cache_dir' => $config['cache_dir'] ?? (function_exists('storage_path') ? storage_path('framework/cache/elms') : sys_get_temp_dir() . '/elms'),
            'cache_ttl' => $config['cache_ttl'] ?? 43200,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function verify(string $licenseKey, ?string $domain = null): array
    {
        return $this->client->verify($licenseKey, $domain);
    }

    public function isValid(string $licenseKey, ?string $domain = null): bool
    {
        return !empty($this->verify($licenseKey, $domain)['status']);
    }

    /**
     * @return array<string,mixed>
     */
    public function activate(string $licenseKey, ?string $domain = null): array
    {
        return $this->client->activate($licenseKey, $domain);
    }

    /**
     * @return array<string,mixed>
     */
    public function deactivate(string $licenseKey, ?string $domain = null): array
    {
        return $this->client->deactivate($licenseKey, $domain);
    }

    /**
     * @return array<string,mixed>
     */
    public function checkUpdate(string $currentVersion, ?string $licenseKey = null): array
    {
        return $this->client->checkUpdate($currentVersion, $licenseKey);
    }
}
