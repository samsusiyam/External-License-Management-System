<?php

namespace App\Middleware;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Models\RateLimit;

/**
 * RateLimitMiddleware
 *
 * Sliding-window rate limiting keyed by API key (if present) or client IP.
 * Configured via RATE_LIMIT_MAX and RATE_LIMIT_WINDOW.
 */
class RateLimitMiddleware
{
    public function handle(Request $request): void
    {
        $max    = (int) Config::get('security.rate_limit_max', 120);
        $window = max(1, (int) Config::get('security.rate_limit_window', 60));

        $apiKey     = $request->header('x-api-key');
        $identifier = ($apiKey !== null && $apiKey !== '')
            ? 'key:' . substr($apiKey, 0, 40)
            : 'ip:' . $request->ip();

        $windowStart = (int) (floor(time() / $window) * $window);

        $model = new RateLimit();
        $count = $model->hit($identifier, $windowStart);

        // Opportunistic prune (roughly 1% of requests) of old windows.
        if (random_int(1, 100) === 1) {
            $model->prune($windowStart - ($window * 5));
        }

        $remaining = max(0, $max - $count);
        if (!headers_sent()) {
            header('X-RateLimit-Limit: ' . $max);
            header('X-RateLimit-Remaining: ' . $remaining);
            header('X-RateLimit-Reset: ' . ($windowStart + $window));
        }

        if ($count > $max) {
            if (!headers_sent()) {
                header('Retry-After: ' . (($windowStart + $window) - time()));
            }
            Response::error('Rate limit exceeded', 429);
        }
    }
}
