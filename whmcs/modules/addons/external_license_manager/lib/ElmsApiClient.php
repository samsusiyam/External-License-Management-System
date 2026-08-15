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
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Api-Key: ' . $this->apiKey,
                'X-Timestamp: ' . $ts,
                'X-Signature: ' . $sig,
            ],
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$respHeaders) {
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
