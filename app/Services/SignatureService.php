<?php

namespace App\Services;

use App\Core\Config;

/**
 * SignatureService
 *
 * Builds and verifies HMAC-SHA256 request signatures.
 *
 * Signature base string = timestamp + "." + api_key + "." + sha256(body)
 * Signature = HMAC_SHA256(base, secret_key)
 *
 * Clients must send:
 *   X-Api-Key   : the public API key
 *   X-Timestamp : unix timestamp (seconds)
 *   X-Signature : hex HMAC as described above
 */
class SignatureService
{
    /**
     * Build the canonical signature for a request.
     */
    public static function build(string $apiKey, string $secret, int $timestamp, string $body): string
    {
        $base = self::baseString($apiKey, $timestamp, $body);
        return hash_hmac('sha256', $base, $secret);
    }

    public static function baseString(string $apiKey, int $timestamp, string $body): string
    {
        return $timestamp . '.' . $apiKey . '.' . hash('sha256', $body);
    }

    /**
     * Verify a provided signature using a constant-time comparison,
     * and enforce the timestamp skew window to block replay attacks.
     */
    public static function verify(
        string $apiKey,
        string $secret,
        ?string $timestamp,
        ?string $signature,
        string $body
    ): bool {
        if ($timestamp === null || $signature === null || $signature === '') {
            return false;
        }
        if (filter_var($timestamp, FILTER_VALIDATE_INT) === false) {
            return false;
        }

        $ts   = (int) $timestamp;
        $skew = (int) Config::get('security.signature_max_skew', 300);
        if (abs(time() - $ts) > $skew) {
            return false;
        }

        $expected = self::build($apiKey, $secret, $ts, $body);
        return hash_equals($expected, $signature);
    }
}
