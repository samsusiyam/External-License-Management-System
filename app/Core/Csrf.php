<?php

namespace App\Core;

/**
 * Csrf
 *
 * Session-backed CSRF token generation and verification for admin forms.
 */
class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function verify(?string $token): bool
    {
        $stored = $_SESSION[self::KEY] ?? '';
        if ($stored === '' || $token === null || $token === '') {
            return false;
        }
        return hash_equals($stored, $token);
    }

    /**
     * Hidden input field for HTML forms.
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }
}
