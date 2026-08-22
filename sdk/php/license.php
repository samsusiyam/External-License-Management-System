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
    private int $signatureMaxSkew;
    /** @var array{sig:?string,ts:?int} */
    private array $lastResponse = ['sig' => null, 'ts' => null];

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
        $this->signatureMaxSkew = $config['signature_max_skew'] ?? 300;
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    /**
     * One-liner quick verification helper:
     * ElmsLicense::check('https://lic.example.com', 'API_KEY', 'SECRET', 'XXXX-XXXX-XXXX-XXXX', 'product-key');
     *
     * @return array<string,mixed>
     */
    public static function check(string $server, string $apiKey, string $secret, string $licenseKey, ?string $product = null, ?string $domain = null): array
    {
        $client = new self([
            'server'  => $server,
            'api_key' => $apiKey,
            'secret'  => $secret,
            'product' => $product ?? '',
        ]);
        return $client->verify($licenseKey, $domain);
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
                $this->writeCache($licenseKey, $domain, $res, (string) $this->lastResponse['sig'], (int) $this->lastResponse['ts']);
            }
            return $res;
        } catch (\Throwable $e) {
            // Network failure: fall back to a recent signed cached result if present.
            $cached = $this->readCache($licenseKey, $domain);
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

        $respHeaders = [];

        if (function_exists('curl_init')) {
            $ch = curl_init($this->server . $path);
            curl_setopt_array($ch, [
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => $body,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_TIMEOUT         => 15,
                CURLOPT_HTTPHEADER      => $headers,
                CURLOPT_SSL_VERIFYPEER  => true,
                CURLOPT_SSL_VERIFYHOST  => 2,
                CURLOPT_HEADERFUNCTION  => function ($ch, string $line) use (&$respHeaders): int {
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
                throw new \RuntimeException('cURL error: ' . $err);
            }
        } else {
            // Fallback to stream context.
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => implode("\r\n", $headers),
                    'content' => $body,
                    'timeout' => 15,
                ],
                'ssl' => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ]);
            $resp = @file_get_contents($this->server . $path, false, $ctx);
            if ($resp === false) {
                throw new \RuntimeException('HTTP request failed');
            }
            if (isset($http_response_header)) {
                foreach ($http_response_header as $line) {
                    $line = trim($line);
                    if ($line !== '' && !str_starts_with($line, 'HTTP/')) {
                        $parts = explode(':', $line, 2);
                        if (count($parts) === 2) {
                            $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                        }
                    }
                }
            }
        }

        // Verify the server's response signature before trusting the body.
        // Without this a MITM could forge a "valid" license response.
        $this->verifyResponseSignature((string) $resp, $respHeaders);
        $this->lastResponse['sig'] = $respHeaders['x-signature'] ?? null;
        $this->lastResponse['ts']  = isset($respHeaders['x-timestamp']) ? (int) $respHeaders['x-timestamp'] : null;

        $decoded = json_decode($resp, true);
        return is_array($decoded) ? $decoded : ['status' => false, 'message' => 'Invalid response', 'data' => []];
    }

    /**
     * Verify the HMAC-SHA256 signature returned by the license server.
     *
     * @param array<string,string> $respHeaders
     */
    private function verifyResponseSignature(string $body, array $respHeaders): void
    {
        $ts  = $respHeaders['x-timestamp'] ?? null;
        $sig = $respHeaders['x-signature'] ?? null;

        if ($ts === null || $sig === null) {
            throw new \RuntimeException('Untrusted server response: missing signature');
        }
        if (!ctype_digit($ts) || abs(time() - (int) $ts) > $this->signatureMaxSkew) {
            throw new \RuntimeException('Untrusted server response: timestamp out of range');
        }
        $expected = hash_hmac('sha256', $ts . '.' . $this->apiKey . '.' . hash('sha256', $body), $this->secret);
        if (!hash_equals($expected, $sig)) {
            throw new \RuntimeException('Untrusted server response: invalid signature');
        }
    }

    private function cacheFile(string $licenseKey, ?string $domain = null): string
    {
        $key = $licenseKey . '|' . $this->product . '|' . ($domain ?? '');
        return $this->cacheDir . '/' . hash('sha256', $key) . '.json';
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function writeCache(string $licenseKey, ?string $domain, array $payload, string $sig, int $ts): void
    {
        @file_put_contents($this->cacheFile($licenseKey, $domain), json_encode([
            'ts'      => $ts,
            'sig'     => $sig,
            'payload' => $payload,
        ]));
    }

    /**
     * Returns a cached payload only if it was signed by the server and
     * the signature still validates (prevents offline tampering of cache).
     *
     * @return array<string,mixed>|null
     */
    private function readCache(string $licenseKey, ?string $domain = null): ?array
    {
        $file = $this->cacheFile($licenseKey, $domain);
        if (!is_file($file)) {
            return null;
        }
        $raw = json_decode((string) file_get_contents($file), true);
        if (!is_array($raw) || !isset($raw['payload'], $raw['sig'], $raw['ts'])) {
            return null;
        }
        if ((time() - (int) $raw['ts']) > $this->cacheTtl) {
            return null;
        }
        $body     = json_encode($raw['payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expected = hash_hmac('sha256', $raw['ts'] . '.' . $this->apiKey . '.' . hash('sha256', $body), $this->secret);
        if (!hash_equals($expected, (string) $raw['sig'])) {
            return null;
        }
        return $raw['payload'];
    }
}

}
