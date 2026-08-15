<?php

namespace App\Services;

/**
 * KeyGenerator
 *
 * Generates human-friendly license keys and cryptographically
 * strong API keys/secrets.
 */
class KeyGenerator
{
    /**
     * Generate a license key in the format XXXX-XXXX-XXXX-XXXX
     * using an unambiguous uppercase alphabet.
     */
    public static function licenseKey(int $groups = 4, int $groupLen = 4): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no I,O,0,1
        $max = strlen($alphabet) - 1;
        $segments = [];
        for ($g = 0; $g < $groups; $g++) {
            $seg = '';
            for ($i = 0; $i < $groupLen; $i++) {
                $seg .= $alphabet[random_int(0, $max)];
            }
            $segments[] = $seg;
        }
        return implode('-', $segments);
    }

    /**
     * Generate a public API key with a recognizable prefix.
     */
    public static function apiKey(): string
    {
        return 'elms_pk_' . bin2hex(random_bytes(16));
    }

    /**
     * Generate a strong API secret.
     */
    public static function apiSecret(): string
    {
        return 'elms_sk_' . bin2hex(random_bytes(24));
    }
}
