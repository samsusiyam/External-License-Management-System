<?php

namespace App\Services;

use App\Models\AuditLog;

/**
 * AuditService
 *
 * Centralized audit logging. Records who did what, to which entity,
 * with contextual details and the source IP.
 */
class AuditService
{
    /**
     * @param array<string,mixed> $details
     */
    public static function log(
        string $action,
        string $actorType = 'system',
        ?string $actorId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        array $details = [],
        ?string $ip = null
    ): void {
        try {
            (new AuditLog())->create([
                'actor_type'  => $actorType,
                'actor_id'    => $actorId,
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId !== null ? (string) $entityId : null,
                'details'     => $details === [] ? null : json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'ip'          => $ip,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging break the main flow.
            error_log('[ELMS audit] ' . $e->getMessage());
        }
    }

    /**
     * @param array<string,mixed> $details
     */
    public static function admin(string $action, array $details = [], ?string $entityType = null, ?string $entityId = null): void
    {
        $admin = $_SESSION['admin'] ?? null;
        self::log(
            $action,
            'admin',
            $admin['username'] ?? null,
            $entityType,
            $entityId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null
        );
    }
}
