<?php

/**
 * ELMS API Client for WHMCS
 *
 * Signs and sends requests to the ELMS License Server using
 * HMAC-SHA256 (timestamp + api_key + sha256(body)).
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class ElmsApiClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;

    public function __construct(string $baseUrl, string $apiKey, string $apiSecret)
    {
        $this->baseUrl   = rtrim($baseUrl, '/');
        $this->apiKey    = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Build a client from stored addon settings.
     */
    public static function fromSettings(): ?self
    {
        try {
            $rows = \WHMCS\Database\Capsule::table('tbladdonmodules')
                ->where('module', 'external_license_manager')
                ->pluck('value', 'setting');
            $s = is_object($rows) ? $rows->toArray() : (array) $rows;
        } catch (\Throwable $e) {
            return null;
        }

        if (empty($s['server_url']) || empty($s['api_key']) || empty($s['api_secret'])) {
            return null;
        }
        return new self($s['server_url'], $s['api_key'], $s['api_secret']);
    }

    /**
     * Test connection to the license server.
     *
     * @return array{status:bool,message:string,latency_ms:float,server_url:string}
     */
    public function testConnection(): array
    {
        $start = microtime(true);
        $res = $this->post('/api/license/verify', [
            'license_key' => 'TEST-PING-' . time(),
            'domain'      => 'ping.test',
        ]);
        $latency = round((microtime(true) - $start) * 1000, 2);

        // If the server answered with signature (even if license invalid), connection & HMAC are working!
        if (isset($res['status'])) {
            return [
                'status'     => true,
                'message'    => 'Connected successfully! Server is online and HMAC signature verified.',
                'latency_ms' => $latency,
                'server_url' => $this->baseUrl,
            ];
        }

        return [
            'status'     => false,
            'message'    => $res['message'] ?? 'Connection failed',
            'latency_ms' => $latency,
            'server_url' => $this->baseUrl,
        ];
    }

    /**
     * Helper to create a license.
     *
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function createLicense(array $payload): array
    {
        return $this->post('/api/license/create', $payload);
    }

    /**
     * Helper to verify a license.
     *
     * @return array<string,mixed>
     */
    public function verifyLicense(string $licenseKey, ?string $domain = null, ?string $product = null): array
    {
        return $this->post('/api/license/verify', [
            'license_key' => $licenseKey,
            'domain'      => $domain,
            'product'     => $product,
        ]);
    }

    /**
     * Helper to renew/extend license.
     *
     * @return array<string,mixed>
     */
    public function renewLicense(string $licenseKey, ?string $expiryDate = null): array
    {
        return $this->post('/api/license/renew', [
            'license_key' => $licenseKey,
            'expiry_date' => $expiryDate,
        ]);
    }

    /**
     * Helper to change status (suspend, unsuspend, terminate).
     *
     * @return array<string,mixed>
     */
    public function changeStatus(string $licenseKey, string $action): array
    {
        $action = trim(strtolower($action));
        $endpoint = match ($action) {
            'suspend'   => '/api/license/suspend',
            'unsuspend' => '/api/license/unsuspend',
            'terminate' => '/api/license/terminate',
            default     => '/api/license/suspend',
        };

        return $this->post($endpoint, ['license_key' => $licenseKey]);
    }

    /**
     * Helper to reset domain/IP bindings.
     *
     * @return array<string,mixed>
     */
    public function resetLicense(string $licenseKey): array
    {
        return $this->post('/api/license/reset', ['license_key' => $licenseKey]);
    }

    /**
     * Signed POST request.
     *
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function post(string $path, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $ts   = time();
        $sig  = hash_hmac('sha256', $ts . '.' . $this->apiKey . '.' . hash('sha256', $body), $this->apiSecret);

        $ch = curl_init($this->baseUrl . $path);
        $respHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 ELMS-WHMCS-Client/2.0',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Api-Key: ' . $this->apiKey,
                'X-Timestamp: ' . $ts,
                'X-Signature: ' . $sig,
            ],
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$respHeaders): int {
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
            return ['status' => false, 'message' => 'Connection error: ' . $err];
        }

        // Verify the server's response signature to reject MITM forgeries.
        $rTs  = $respHeaders['x-timestamp'] ?? null;
        $rSig = $respHeaders['x-signature'] ?? null;
        if ($rTs === null || $rSig === null || !ctype_digit($rTs) || abs(time() - (int) $rTs) > 300) {
            return ['status' => false, 'message' => 'Untrusted server response (no/invalid signature)'];
        }
        $expected = hash_hmac('sha256', $rTs . '.' . $this->apiKey . '.' . hash('sha256', $resp), $this->apiSecret);
        if (!hash_equals($expected, $rSig)) {
            return ['status' => false, 'message' => 'Untrusted server response (bad signature)'];
        }

        $decoded = json_decode($resp, true);
        return is_array($decoded) ? $decoded : ['status' => false, 'message' => 'Invalid response'];
    }
}
