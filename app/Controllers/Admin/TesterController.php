<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Request;
use App\Models\ApiKey;
use App\Models\License;
use App\Models\Product;
use App\Services\LicenseService;
use App\Services\SignatureService;

/**
 * TesterController
 *
 * Provides an in-browser API Tester & License Simulator for admins.
 */
class TesterController extends Controller
{
    public function index(Request $request): void
    {
        $apiKeys  = (new ApiKey())->where(['status' => 'active'], 'id DESC');
        $products = (new Product())->all('product_name ASC');
        $licenses = (new License())->all('id DESC', 20);

        $appUrl = rtrim((string) Config::get('app.url', 'http://localhost'), '/');

        $this->view('tester/index', [
            'title'    => 'API Tester & Simulator',
            'apiKeys'  => $apiKeys,
            'products' => $products,
            'licenses' => $licenses,
            'appUrl'   => $appUrl,
            'flash'    => self::pullFlash(),
        ]);
    }

    public function run(Request $request): void
    {
        $endpoint = trim((string) $request->input('endpoint', '/api/license/verify'));
        $apiKeyId = (int) $request->input('api_key_id', 0);
        $rawPayload = $request->input('payload');

        if (is_array($rawPayload)) {
            $payload = $rawPayload;
        } else {
            $payload = json_decode((string) $rawPayload, true);
            if (!is_array($payload)) {
                $payload = [];
            }
        }

        $apiKeyRow = null;
        if ($apiKeyId > 0) {
            $apiKeyRow = (new ApiKey())->find($apiKeyId);
        }
        if ($apiKeyRow === null) {
            $apiKeyRow = (new ApiKey())->findBy(['status' => 'active']);
        }

        $apiKey = $apiKeyRow['api_key'] ?? (string) $request->input('custom_api_key', '');
        $secret = $apiKeyRow['secret_key'] ?? (string) $request->input('custom_api_secret', '');

        if ($apiKey === '' || $secret === '') {
            $this->json([
                'success' => false,
                'message' => 'No active API Key found. Please create an API Key first under API Keys menu.',
            ], 400);
        }

        // Build URL
        $base = Request::basePath();
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
        $serverUrl = $scheme . '://' . $host . $base;

        $targetUrl = $serverUrl . $endpoint;
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $ts = time();
        $sig = SignatureService::build($apiKey, $secret, $ts, $body);

        $headers = [
            'Content-Type: application/json',
            'X-Api-Key: ' . $apiKey,
            'X-Timestamp: ' . $ts,
            'X-Signature: ' . $sig,
        ];

        $start = microtime(true);
        $respHeaders = [];

        $ch = curl_init($targetUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 ELMS-License-Client/2.0',
            CURLOPT_HTTPHEADER     => $headers,
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

        $responseBody = curl_exec($ch);
        $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        $durationMs = round((microtime(true) - $start) * 1000, 2);

        $isCloudflareBlocked = false;
        $cfNotice = null;

        if ($httpCode === 403 || (is_string($responseBody) && (str_contains($responseBody, 'challenges.cloudflare.com') || str_contains($responseBody, 'Just a moment...')))) {
            $isCloudflareBlocked = true;
            $cfNotice = '⚠️ Cloudflare WAF / Bot Fight Mode blocked the loopback HTTP request. To fix this for WHMCS & clients, go to your Cloudflare Dashboard > Security > WAF and create a Rule to Skip/Bypass WAF for URI Path starting with /api/ (or disable Bot Fight Mode for lic.hostnibo.com).';

            // Run direct internal service simulation so admin can still verify the response immediately
            $jsonDecoded = $this->simulateLocalService($endpoint, $payload);
            $httpCode = 200;
        } else {
            $jsonDecoded = json_decode((string) $responseBody, true);
        }

        // Verify response signature if present
        $serverSigValid = null;
        if (!empty($respHeaders['x-signature']) && !empty($respHeaders['x-timestamp'])) {
            $expectedRespSig = hash_hmac('sha256', $respHeaders['x-timestamp'] . '.' . $apiKey . '.' . hash('sha256', (string) $responseBody), $secret);
            $serverSigValid = hash_equals($expectedRespSig, (string) $respHeaders['x-signature']);
        }

        $this->json([
            'success'             => $curlError === '' && $httpCode >= 200 && $httpCode < 400,
            'target_url'          => $targetUrl,
            'http_code'           => $httpCode,
            'duration_ms'         => $durationMs,
            'curl_error'          => $curlError ?: null,
            'cloudflare_blocked'  => $isCloudflareBlocked,
            'cloudflare_notice'   => $cfNotice,
            'request_headers'     => [
                'X-Api-Key'   => $apiKey,
                'X-Timestamp' => $ts,
                'X-Signature' => $sig,
                'Content-Type'=> 'application/json',
            ],
            'request_body'        => $payload,
            'response_headers'    => $respHeaders,
            'response_body'       => $jsonDecoded ?? $responseBody,
            'server_sig_valid'    => $serverSigValid,
        ]);
    }

    /**
     * Fallback simulator if Cloudflare or external loopback firewall intervenes.
     *
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function simulateLocalService(string $endpoint, array $payload): array
    {
        $service = new LicenseService();
        $licKey  = (string) ($payload['license_key'] ?? '');
        $domain  = !empty($payload['domain']) ? (string) $payload['domain'] : null;
        $ip      = !empty($payload['ip']) ? (string) $payload['ip'] : null;
        $product = !empty($payload['product']) ? (string) $payload['product'] : null;

        return match ($endpoint) {
            '/api/license/create'     => $service->create($payload),
            '/api/license/verify'     => $service->verify($licKey, $domain, $ip, $product),
            '/api/license/activate'   => $service->activate($licKey, $domain, $ip, $product, gethostname() ?: null, null),
            '/api/license/deactivate' => $service->deactivate($licKey, $domain),
            '/api/license/renew'      => $service->renew($licKey, $payload['expiry_date'] ?? null),
            '/api/license/reset'      => $service->reset($licKey),
            '/api/license/suspend'    => $service->setStatus($licKey, 'suspended'),
            '/api/license/unsuspend'  => $service->setStatus($licKey, 'active'),
            '/api/license/terminate'  => $service->setStatus($licKey, 'terminated'),
            default                   => $service->verify($licKey, $domain, $ip, $product),
        };
    }
}
