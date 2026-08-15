<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Models\ApiKey;
use App\Services\SignatureService;

/**
 * ApiAuthMiddleware
 *
 * Authenticates machine API requests using an API key + HMAC-SHA256
 * signature with a timestamp replay window.
 *
 * Required headers:
 *   X-Api-Key, X-Timestamp, X-Signature
 *
 * On success, the resolved api key row is stored in a request-scoped
 * static context so controllers/logging can reference it.
 */
class ApiAuthMiddleware
{
    /** @var array<string,mixed>|null */
    private static ?array $currentKey = null;

    public function handle(Request $request): void
    {
        $apiKey    = $request->header('x-api-key');
        $timestamp = $request->header('x-timestamp');
        $signature = $request->header('x-signature');

        if ($apiKey === null || $apiKey === '') {
            Response::error('Missing API key', 401);
        }

        $keyRow = (new ApiKey())->findActiveByKey($apiKey);
        if ($keyRow === null) {
            Response::error('Invalid or revoked API key', 401);
        }

        $valid = SignatureService::verify(
            (string) $keyRow['api_key'],
            (string) $keyRow['secret_key'],
            $timestamp,
            $signature,
            $request->rawBody()
        );

        if (!$valid) {
            Response::error('Invalid signature or expired timestamp', 401);
        }

        self::$currentKey = $keyRow;
        (new ApiKey())->touchUsed((int) $keyRow['id']);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function currentKey(): ?array
    {
        return self::$currentKey;
    }
}
