<?php

namespace App\Services;

use App\Models\ApiLog;
use App\Models\AuditLog;

/**
 * SettingService
 *
 * Lightweight key-value configuration manager stored in storage/settings.json
 * with automatic log pruning capabilities.
 */
class SettingService
{
    private static string $file = '';

    private static function getFilePath(): string
    {
        if (self::$file === '') {
            self::$file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'settings.json';
        }
        return self::$file;
    }

    /**
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        $file = self::getFilePath();
        if (!is_file($file)) {
            return [
                'api_log_retention_days'   => 30, // 0 = disabled, or 7, 14, 30, 60, 90, etc.
                'audit_log_retention_days' => 90,
                'auto_prune_api_logs'      => 1,
                'last_prune_at'            => null,
            ];
        }
        $data = json_decode((string) @file_get_contents($file), true);
        return is_array($data) ? $data : [
            'api_log_retention_days'   => 30,
            'audit_log_retention_days' => 90,
            'auto_prune_api_logs'      => 1,
            'last_prune_at'            => null,
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $all = self::all();
        $all[$key] = $value;
        $dir = dirname(self::getFilePath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(self::getFilePath(), json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param array<string,mixed> $settings
     */
    public static function setMultiple(array $settings): void
    {
        $all = array_merge(self::all(), $settings);
        $dir = dirname(self::getFilePath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(self::getFilePath(), json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Run opportunistic log pruning based on configured retention policies.
     *
     * @return array{pruned_api:int,pruned_audit:int}
     */
    public static function autoPruneIfNeeded(bool $force = false): array
    {
        $settings = self::all();
        $apiRetention = (int) ($settings['api_log_retention_days'] ?? 30);
        $autoPrune = !empty($settings['auto_prune_api_logs']);

        $lastPrune = $settings['last_prune_at'] ?? null;
        $now = time();

        // Unless forced, prune at most once every 30 minutes
        if (!$force) {
            if (!$autoPrune || $apiRetention <= 0) {
                return ['pruned_api' => 0, 'pruned_audit' => 0];
            }
            if ($lastPrune && ($now - strtotime($lastPrune)) < 1800) {
                return ['pruned_api' => 0, 'pruned_audit' => 0];
            }
        }

        $deletedApi = 0;
        $deletedAudit = 0;

        try {
            if ($apiRetention > 0) {
                $deletedApi = (new ApiLog())->deleteOlderThan($apiRetention);
            }

            $auditRetention = (int) ($settings['audit_log_retention_days'] ?? 0);
            if ($auditRetention > 0) {
                $deletedAudit = (new AuditLog())->deleteOlderThan($auditRetention);
            }

            self::set('last_prune_at', date('Y-m-d H:i:s'));
        } catch (\Throwable $e) {
            error_log('[ELMS auto-prune error] ' . $e->getMessage());
        }

        return [
            'pruned_api'   => $deletedApi,
            'pruned_audit' => $deletedAudit,
        ];
    }
}
