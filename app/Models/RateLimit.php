<?php

namespace App\Models;

use App\Core\Model;

/**
 * RateLimit
 *
 * Sliding-window counters keyed by identifier (api key or ip) and
 * the start of the current window bucket.
 */
class RateLimit extends Model
{
    protected string $table = 'rate_limits';

    /**
     * Increment the counter for the given identifier within the current
     * window and return the resulting count. Uses an atomic upsert.
     */
    public function hit(string $identifier, int $windowStart): int
    {
        $this->db()->run(
            "INSERT INTO `rate_limits` (identifier, window_start, request_count)
             VALUES (:id, :ws, 1)
             ON DUPLICATE KEY UPDATE request_count = request_count + 1",
            ['id' => $identifier, 'ws' => $windowStart]
        );

        return (int) $this->db()->scalar(
            "SELECT request_count FROM `rate_limits` WHERE identifier = :id AND window_start = :ws",
            ['id' => $identifier, 'ws' => $windowStart]
        );
    }

    /**
     * Delete old window rows to keep the table small.
     */
    public function prune(int $olderThan): int
    {
        return $this->db()->run(
            "DELETE FROM `rate_limits` WHERE window_start < :ws",
            ['ws' => $olderThan]
        )->rowCount();
    }

    /**
     * Remove all counters for an identifier (used to reset a login lockout).
     */
    public function clear(string $identifier): int
    {
        return $this->db()->run(
            "DELETE FROM `rate_limits` WHERE identifier = :id",
            ['id' => $identifier]
        )->rowCount();
    }
}
