<?php

use App\Services\SignatureService;

$apiKey = 'elms_pk_test123';
$secret = 'elms_sk_supersecret';
$body   = (string) json_encode(['license_key' => 'ABCD-1234', 'domain' => 'example.com']);

elms_test('signature_verify_valid', function () use ($apiKey, $secret, $body): void {
    $ts = time();
    $sig = SignatureService::build($apiKey, $secret, $ts, $body);
    assert_true(SignatureService::verify($apiKey, $secret, (string) $ts, $sig, $body));
});

elms_test('signature_reject_tampered_body', function () use ($apiKey, $secret, $body): void {
    $ts = time();
    $sig = SignatureService::build($apiKey, $secret, $ts, $body);
    assert_false(SignatureService::verify($apiKey, $secret, (string) $ts, $sig, $body . 'x'));
});

elms_test('signature_reject_wrong_secret', function () use ($apiKey, $secret, $body): void {
    $ts = time();
    $sig = SignatureService::build($apiKey, $secret, $ts, $body);
    assert_false(SignatureService::verify($apiKey, 'wrong-secret', (string) $ts, $sig, $body));
});

elms_test('signature_reject_expired_timestamp', function () use ($apiKey, $secret, $body): void {
    $ts = time() - 400; // beyond default 300s skew
    $sig = SignatureService::build($apiKey, $secret, $ts, $body);
    assert_false(SignatureService::verify($apiKey, $secret, (string) $ts, $sig, $body));
});

elms_test('signature_reject_future_timestamp', function () use ($apiKey, $secret, $body): void {
    $ts = time() + 400;
    $sig = SignatureService::build($apiKey, $secret, $ts, $body);
    assert_false(SignatureService::verify($apiKey, $secret, (string) $ts, $sig, $body));
});

elms_test('signature_reject_missing_timestamp', function () use ($apiKey, $secret, $body): void {
    $ts = time();
    $sig = SignatureService::build($apiKey, $secret, $ts, $body);
    assert_false(SignatureService::verify($apiKey, $secret, null, $sig, $body));
    assert_false(SignatureService::verify($apiKey, $secret, '', $sig, $body));
});
