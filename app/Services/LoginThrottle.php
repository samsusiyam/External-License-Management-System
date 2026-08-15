<?php

namespace App\Services;

use App\Core\Config;
use App\Models\RateLimit;

/**
 * LoginThrottle
 *
 * Brute-force protection for the admin login form. Counts failed login
 * attempts per client IP (within a sliding window) and blocks further
 * attempts once the threshold is reached.
 *
 * Uses the existing rate_limits table. Failure counts are reset on a
 * successful login so legitimate admins are not penalised.
 */
class LoginThrottle
{
    private string $identifier;
    private int $max;
    private int $window;
    private RateLimit $model;

    public function __construct(string $ip, ?RateLimit $model = null)
    {
        // Sanitize the IP so it is safe to use inside the identifier key.
        $safe = preg_replace('/[^A-Za-z0-9.:_\-]/', '_', $ip);
        $this->identifier = 'login:' . ($safe !== '' ? $safe : 'unknown');
        $this->max    = max(1, (int) Config::get('security.login_max_attempts', 5));
        $this->window = max(1, (int) Config::get('security.login_window', 900));
        $this->model  = $model ?? new RateLimit();
    }

    private function windowStart(): int
    {
        return (int) (floor(time() / $this->window) * $this->window);
    }

    /**
     * Whether the caller has exhausted the allowed failed attempts.
     */
    public function isBlocked(): bool
    {
        $count = (int) $this->model->count(
            "identifier = :id AND window_start = :ws",
            ['id' => $this->identifier, 'ws' => $this->windowStart()]
        );
        return $count >= $this->max;
    }

    /**
     * Record one failed attempt.
     */
    public function recordFailure(): void
    {
        $this->model->hit($this->identifier, $this->windowStart());
    }

    /**
     * Reset the failure counter (call after a successful login).
     */
    public function clear(): void
    {
        $this->model->clear($this->identifier);
    }
}
