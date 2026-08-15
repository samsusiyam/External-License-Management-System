<?php
/**
 * ELMS API smoke test (CLI).
 * Exercises the signed REST API against a running server.
 *
 * Usage: php scripts/smoke_test.php <api_key> <api_secret> [base_url]
 */
declare(strict_types=1);

$apiKey    = $argv[1] ?? '';
$apiSecret = $argv[2] ?? '';
$base      = rtrim($argv[3] ?? 'http://127.0.0.1:8080', '/');

if ($apiKey === '' || $apiSecret === '') {
    fwrite(STDERR, "Usage: php smoke_test.php <api_key> <api_secret> [base_url]\n");
    exit(1);
}

function call(string $base, string $key, string $secret, string $path, array $payload): array
{
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $ts   = time();
    $sig  = hash_hmac('sha256', $ts . '.' . $key . '.' . hash('sha256', $body), $secret);

    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Api-Key: ' . $key,
            'X-Timestamp: ' . $ts,
            'X-Signature: ' . $sig,
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $resp];
}

function show(string $label, array $r): void
{
    echo str_pad($label, 26) . " [{$r['code']}] {$r['body']}\n";
}

echo "== ELMS API smoke test ==\n";

$r = call($base, $apiKey, $apiSecret, '/api/license/create', [
    'product_id' => 1, 'customer_name' => 'John Doe', 'customer_email' => 'john@example.com',
    'expiry_date' => '2027-01-01', 'activation_limit' => 2, 'domain_lock' => 1,
]);
show('create', $r);
$data = json_decode($r['body'], true);
$key  = $data['data']['license_key'] ?? '';
echo "   -> license_key: {$key}\n";

show('verify', call($base, $apiKey, $apiSecret, '/api/license/verify', [
    'license_key' => $key, 'domain' => 'example.com', 'product' => 'WHMCS-OTP',
]));

show('activate #1', call($base, $apiKey, $apiSecret, '/api/license/activate', [
    'license_key' => $key, 'domain' => 'example.com', 'ip' => '203.0.113.10',
    'server_hostname' => 'web01', 'install_path' => '/var/www',
]));

show('verify wrong domain', call($base, $apiKey, $apiSecret, '/api/license/verify', [
    'license_key' => $key, 'domain' => 'evil.com', 'product' => 'WHMCS-OTP',
]));

show('suspend', call($base, $apiKey, $apiSecret, '/api/license/suspend', ['license_key' => $key]));
show('verify suspended', call($base, $apiKey, $apiSecret, '/api/license/verify', ['license_key' => $key]));
show('unsuspend', call($base, $apiKey, $apiSecret, '/api/license/unsuspend', ['license_key' => $key]));
show('deactivate', call($base, $apiKey, $apiSecret, '/api/license/deactivate', [
    'license_key' => $key, 'domain' => 'example.com',
]));
show('reset', call($base, $apiKey, $apiSecret, '/api/license/reset', ['license_key' => $key]));
show('renew', call($base, $apiKey, $apiSecret, '/api/license/renew', [
    'license_key' => $key, 'expiry_date' => '2028-06-30',
]));
show('updates/check', call($base, $apiKey, $apiSecret, '/api/updates/check', [
    'product' => 'WHMCS-OTP', 'current_version' => '0.9.0',
]));

$ch = curl_init($base . '/api/license/verify');
curl_setopt_array($ch, [
    CURLOPT_POST => true, CURLOPT_POSTFIELDS => '{}', CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Api-Key: ' . $apiKey, 'X-Timestamp: ' . time(), 'X-Signature: deadbeef'],
]);
$bad = curl_exec($ch); $badCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
show('bad signature', ['code' => $badCode, 'body' => $bad]);

echo "== done ==\n";
