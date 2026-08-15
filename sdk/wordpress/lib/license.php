<?php

/**
 * ELMS PHP SDK — single-file client for verifying software licenses.
 *
 * Drop this file into your product and call:
 *
 *   require __DIR__ . '/license.php';
 *
 *   $elms = new ElmsLicense([
 *       'server'  => 'https://license.example.com',
 *       'api_key' => 'elms_pk_xxx',
 *       'secret'  => 'elms_sk_xxx',
 *       'product' => 'WHMCS-OTP',
 *   ]);
 *
 *   $result = $elms->verify('XXXX-XXXX-XXXX-XXXX', 'example.com');
 *   if ($result['status']) {
 *       // license valid — continue
 *   } else {
 *       // disable / show notice
 *   }
 *
 * Features:
 *  - HMAC-SHA256 request signing (replay-protected via timestamp)
 *  - Local response caching to reduce calls and tolerate outages
 *  - activate() / deactivate() helpers
 */

if (!class_exists('ElmsLicense')) {

class ElmsLicense
{
    private string $server;
    private string $apiKey;
    private string $secret;
    private string $product;
    private string $cacheDir;
    private int $cacheTtl;

    /**
     * @param array{server:string,api_key:string,secret:string,product?:string,cache_dir?:string,cache_ttl?:int} $config
     */
    public function __construct(array $config)
    {
        $this->server   = rtrim($config['server'], '/');
        $this->apiKey   = $config['api_key'];
        $this->secret   = $config['secret'];
        $this->product  = $config['product'] ?? '';
        $this->cacheDir = $config['cache_dir'] ?? sys_get_temp_dir() . '/elms_cache';
        $this->cacheTtl = $config['cache_ttl'] ?? 43200; // 12h
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    /**
     * Verify a license. Returns the decoded API response array
     * (['status'=>bool, 'message'=>string, 'data'=>[...]]).
     *
     * @return array<string,mixed>
     */
    public function verify(string $licenseKey, ?string $domain = null, ?string $ip = null): array
    {
        $domain = $domain ?? ($_SERVER['HTTP_HOST'] ?? null);
        $ip     = $ip ?? ($_SERVER['SERVER_ADDR'] ?? null);

        $payload = [
            'license_key' => $licenseKey,
            'domain'      => $domain,
            'ip'          => $ip,
            'product'     => $this->product,
        ];

        try {
            $res = $this->post('/api/license/verify', $payload);
            // Cache successful verifications so brief outages don't disable the product.
            if (!empty($res['status'])) {
                $this->writeCache($licenseKey, $res);
            }
            return $res;
        } catch (\Throwable $e) {
            // Network failure: fall back to a recent cached result if present.
            $cached = $this->readCache($licenseKey);
            if ($cached !== null) {
                $cached['message'] = ($cached['message'] ?? 'License Valid') . ' (cached)';
                return $cached;
            }
            return ['status' => false, 'message' => 'License server unreachable', 'data' => []];
        }
    }

    /**
     * Activate the license for this install.
     *
     * @return array<string,mixed>
     */
    public function activate(string $licenseKey, ?string $domain = null, ?string $ip = null): array
    {
        return $this->post('/api/license/activate', [
            'license_key'     => $licenseKey,
            'domain'          => $domain ?? ($_SERVER['HTTP_HOST'] ?? null),
            'ip'              => $ip ?? ($_SERVER['SERVER_ADDR'] ?? null),
            'product'         => $this->product,
            'server_hostname' => gethostname() ?: null,
            'install_path'    => __DIR__,
        ]);
    }

    /**
     * Deactivate this install.
     *
     * @return array<string,mixed>
     */
    public function deactivate(string $licenseKey, ?string $domain = null): array
    {
        return $this->post('/api/license/deactivate', [
            'license_key' => $licenseKey,
            'domain'      => $domain ?? ($_SERVER['HTTP_HOST'] ?? null),
        ]);
    }

    /**
     * Check for product updates.
     *
     * @return array<string,mixed>
     */
    public function checkUpdate(string $currentVersion, ?string $licenseKey = null): array
    {
        return $this->post('/api/updates/check', [
            'product'         => $this->product,
            'current_version' => $currentVersion,
            'license_key'     => $licenseKey,
        ]);
    }

    /**
     * Signed POST request.
     *
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function post(string $path, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $ts   = time();
        $sig  = hash_hmac('sha256', $ts . '.' . $this->apiKey . '.' . hash('sha256', $body), $this->secret);

        $headers = [
            'Content-Type: application/json',
            'X-Api-Key: ' . $this->apiKey,
            'X-Timestamp: ' . $ts,
            'X-Signature: ' . $sig,
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($this->server . $path);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => $headers,
            ]);
            $resp = curl_exec($ch);
            $err  = curl_error($ch);
            curl_close($ch);
            if ($resp === false) {
                throw new \RuntimeException('cURL error: ' . $err);
            }
        } else {
            // Fallback to stream context.
            $ctx = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 15,
            ]]);
            $resp = @file_get_contents($this->server . $path, false, $ctx);
            if ($resp === false) {
                throw new \RuntimeException('HTTP request failed');
            }
        }

        $decoded = json_decode($resp, true);
        return is_array($decoded) ? $decoded : ['status' => false, 'message' => 'Invalid response', 'data' => []];
    }

    private function cacheFile(string $licenseKey): string
    {
        return $this->cacheDir . '/' . hash('sha256', $licenseKey . '|' . $this->product) . '.json';
    }

    /**
     * @param array<string,mixed> $data
     */
    private function writeCache(string $licenseKey, array $data): void
    {
        @file_put_contents($this->cacheFile($licenseKey), json_encode([
            'ts'   => time(),
            'data' => $data,
        ]));
    }

    /**
     * @return array<string,mixed>|null
     */
    private function readCache(string $licenseKey): ?array
    {
        $file = $this->cacheFile($licenseKey);
        if (!is_file($file)) {
            return null;
        }
        $raw = json_decode((string) file_get_contents($file), true);
        if (!is_array($raw) || (time() - ($raw['ts'] ?? 0)) > $this->cacheTtl) {
            return null;
        }
        return $raw['data'] ?? null;
    }
}

}
