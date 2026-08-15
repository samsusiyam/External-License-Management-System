<?php

use App\Services\KeyGenerator;

elms_test('license_key_format', function (): void {
    $k = KeyGenerator::licenseKey();
    assert_matches('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $k);
});

elms_test('license_key_no_ambiguous_chars', function (): void {
    $k = KeyGenerator::licenseKey();
    assert_false((bool) preg_match('/[ILO0]/', $k));
});

elms_test('license_key_unique_across_batch', function (): void {
    $seen = [];
    for ($i = 0; $i < 2000; $i++) {
        $seen[KeyGenerator::licenseKey()] = true;
    }
    assert_equals(2000, count($seen));
});

elms_test('api_key_has_prefix', function (): void {
    assert_string_contains(KeyGenerator::apiKey(), 'elms_pk_');
});

elms_test('api_secret_has_prefix', function (): void {
    assert_string_contains(KeyGenerator::apiSecret(), 'elms_sk_');
});
