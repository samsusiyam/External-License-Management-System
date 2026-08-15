<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\ApiLog;
use App\Middleware\ApiAuthMiddleware;

/**
 * ApiController
 *
 * Base for API controllers. Provides response helpers that also
 * persist an api_logs entry for observability.
 */
abstract class ApiController
{
    /**
     * Emit a success JSON response (and log it).
     *
     * @param array<string,mixed> $data
     */
    protected function respond(Request $request, bool $ok, string $message, array $data = [], int $status = 200): never
    {
        $payload = [
            'status'  => $ok,
            'message' => $message,
        ];
        if ($data !== []) {
            $payload['data'] = $data;
        } else {
            // Preserve spec fields at top-level for verify responses.
            $payload['data'] = [];
        }

        $this->logRequest($request, $payload, $status, $ok);

        $key    = ApiAuthMiddleware::currentKey();
        $secret = $key !== null ? (string) $key['secret_key'] : '';
        $apiKey = $key !== null ? (string) $key['api_key'] : '';

        Response::jsonSigned($payload, $secret, $apiKey, $status);
    }

    /**
     * Translate a service result array into an HTTP response.
     *
     * @param array{status:bool,message:string,data:array<string,mixed>} $result
     */
    protected function respondService(Request $request, array $result): never
    {
        $status = $result['status'] ? 200 : 422;
        // Auth/validation failures use 4xx; keep verify "invalid" as 200-with-false? -> use 422 for clarity.
        $this->respond($request, $result['status'], $result['message'], $result['data'] ?? [], $status);
    }

    /**
     * Persist the API request/response for the API Logs page.
     *
     * @param array<string,mixed> $payload
     */
    private function logRequest(Request $request, array $payload, int $status, bool $ok): void
    {
        try {
            $key = ApiAuthMiddleware::currentKey();
            (new ApiLog())->create([
                'endpoint'      => $request->path(),
                'method'        => $request->method(),
                'api_key_id'    => $key !== null ? (int) $key['id'] : null,
                'request_body'  => $this->redact($request->rawBody()),
                'response_body' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'status_code'   => $status,
                'success'       => $ok ? 1 : 0,
                'ip'            => $request->ip(),
                'duration_ms'   => (int) ((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('[ELMS api-log] ' . $e->getMessage());
        }
    }

    /**
     * Truncate overly large bodies before logging.
     */
    private function redact(string $body): string
    {
        if (strlen($body) > 4000) {
            return substr($body, 0, 4000) . '...[truncated]';
        }
        return $body;
    }
}
