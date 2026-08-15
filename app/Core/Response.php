<?php

namespace App\Core;

/**
 * Response
 *
 * JSON + HTML response helpers with security headers.
 */
class Response
{
    /**
     * Send a JSON response and terminate.
     *
     * @param array<string,mixed> $payload
     */
    public static function json(array $payload, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store');
        }
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send a signed JSON response and terminate.
     *
     * The response body is HMAC-SHA256 signed with the API key secret so
     * that clients can verify authenticity and reject MITM tampering.
     * Signature base = timestamp "." api_key "." sha256(body)
     * Exposed via the X-Signature / X-Timestamp headers.
     *
     * @param array<string,mixed> $payload
     */
    public static function jsonSigned(array $payload, string $secret, string $apiKey, int $status = 200): never
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            $body = '{}';
        }
        $ts  = time();
        $sig = hash_hmac('sha256', $ts . '.' . $apiKey . '.' . hash('sha256', $body), $secret);

        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store');
            header('X-Timestamp: ' . $ts);
            header('X-Signature: ' . $sig);
        }
        echo $body;
        exit;
    }

    /**
     * Standard success envelope.
     *
     * @param array<string,mixed> $data
     */
    public static function success(string $message = 'OK', array $data = [], int $status = 200): never
    {
        self::json([
            'status'  => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * Standard error envelope.
     *
     * @param array<string,mixed> $data
     */
    public static function error(string $message = 'Error', int $status = 400, array $data = []): never
    {
        self::json([
            'status'  => false,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * Send raw HTML and terminate.
     */
    public static function html(string $html, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/html; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
        echo $html;
        exit;
    }

    /**
     * Redirect to a path/URL and terminate.
     * Root-relative internal paths ("/admin/...") are automatically
     * prefixed with the app base path so it works under a subdirectory.
     */
    public static function redirect(string $to, int $status = 302): never
    {
        if (str_starts_with($to, '/') && !str_starts_with($to, '//')) {
            $to = Request::basePath() . $to;
        }
        if (!headers_sent()) {
            http_response_code($status);
            header('Location: ' . $to);
        }
        exit;
    }
}
