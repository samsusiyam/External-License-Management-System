<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\ApiLog;
use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\SettingService;

/**
 * LogController
 *
 * Paginated views, search, single & bulk deletion, and auto-delete settings for
 * API request logs and audit trail entries.
 */
class LogController extends Controller
{
    private ApiLog $apiLogs;
    private AuditLog $auditLogs;

    public function __construct()
    {
        $this->apiLogs   = new ApiLog();
        $this->auditLogs = new AuditLog();
    }

    public function apiLogs(Request $request): void
    {
        // Opportunistic auto-delete check
        SettingService::autoPruneIfNeeded();

        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(10, min(100, (int) $request->query('per_page', 30)));
        $offset  = ($page - 1) * $perPage;

        $failed  = $request->query('failed') === '1' ? true : null;
        $search  = trim((string) $request->query('search', ''));
        $method  = trim((string) $request->query('method', ''));

        $rows  = $this->apiLogs->paginate($perPage, $offset, $failed, $search !== '' ? $search : null, $method !== '' ? $method : null);
        $total = $this->apiLogs->totalCount($failed, $search !== '' ? $search : null, $method !== '' ? $method : null);

        $settings = SettingService::all();

        $this->view('logs/api', [
            'title'     => 'API Logs',
            'logs'      => $rows,
            'page'      => $page,
            'perPage'   => $perPage,
            'total'     => $total,
            'pages'     => (int) ceil($total / $perPage),
            'failed'    => $failed === true,
            'search'    => $search,
            'method'    => $method,
            'csrf'      => Csrf::token(),
            'settings'  => $settings,
            'flash'     => self::pullFlash(),
        ]);
    }

    public function deleteApiLog(Request $request, array $params): void
    {
        $this->guardCsrf($request, '/admin/logs/api');
        $id = (int) ($params['id'] ?? 0);
        if ($id > 0) {
            $this->apiLogs->deleteById($id);
            AuditService::admin('apilog.deleted', ['id' => $id], 'api_log', (string) $id);
            $this->flash('success', 'API log entry deleted successfully.');
        }
        $this->redirect('/admin/logs/api');
    }

    public function bulkDeleteApiLogs(Request $request): void
    {
        $this->guardCsrf($request, '/admin/logs/api');
        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        if (is_array($ids) && !empty($ids)) {
            $deleted = $this->apiLogs->deleteByIds($ids);
            AuditService::admin('apilog.bulk_deleted', ['count' => $deleted], 'api_log');
            $this->flash('success', "{$deleted} API log entries deleted successfully.");
        } else {
            $this->flash('error', 'No log entries selected for deletion.');
        }
        $this->redirect('/admin/logs/api');
    }

    public function purgeApiLogs(Request $request): void
    {
        $this->guardCsrf($request, '/admin/logs/api');
        $purgeType = (string) $request->input('purge_type', 'days');
        $days      = (int) $request->input('days', 30);

        if ($purgeType === 'all') {
            $deleted = $this->apiLogs->clearAll();
            AuditService::admin('apilog.purged_all', ['count' => $deleted], 'api_log');
            $this->flash('success', "All {$deleted} API log entries have been permanently cleared.");
        } elseif ($purgeType === 'failed') {
            $deleted = $this->apiLogs->clearAll(true);
            AuditService::admin('apilog.purged_failed', ['count' => $deleted], 'api_log');
            $this->flash('success', "{$deleted} failed API log entries cleared.");
        } else {
            $days = max(1, $days);
            $deleted = $this->apiLogs->deleteOlderThan($days);
            AuditService::admin('apilog.purged_older_than', ['days' => $days, 'count' => $deleted], 'api_log');
            $this->flash('success', "{$deleted} API log entries older than {$days} days deleted.");
        }

        $this->redirect('/admin/logs/api');
    }

    public function saveApiLogSettings(Request $request): void
    {
        $this->guardCsrf($request, '/admin/logs/api');
        $retentionDays = max(0, (int) $request->input('api_log_retention_days', 30));
        $autoPrune     = $request->input('auto_prune_api_logs') ? 1 : 0;

        SettingService::setMultiple([
            'api_log_retention_days' => $retentionDays,
            'auto_prune_api_logs'    => $autoPrune,
        ]);

        if ($request->input('run_prune_now') && $retentionDays > 0) {
            $res = SettingService::autoPruneIfNeeded(true);
            $pruned = $res['pruned_api'] ?? 0;
            $this->flash('success', "Auto-delete settings updated. Pruned {$pruned} expired log entries now.");
        } else {
            $this->flash('success', 'Auto-delete settings saved successfully.');
        }

        $this->redirect('/admin/logs/api');
    }

    public function auditLogs(Request $request): void
    {
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(10, min(100, (int) $request->query('per_page', 30)));
        $offset  = ($page - 1) * $perPage;

        $actor  = trim((string) $request->query('actor', ''));
        $search = trim((string) $request->query('search', ''));

        $rows  = $this->auditLogs->paginate($perPage, $offset, $actor !== '' ? $actor : null, $search !== '' ? $search : null);
        $total = $this->auditLogs->totalCount($actor !== '' ? $actor : null, $search !== '' ? $search : null);

        $this->view('logs/audit', [
            'title'   => 'Audit Logs',
            'logs'    => $rows,
            'page'    => $page,
            'perPage' => $perPage,
            'total'   => $total,
            'pages'   => (int) ceil($total / $perPage),
            'actor'   => $actor,
            'search'  => $search,
            'csrf'    => Csrf::token(),
            'flash'   => self::pullFlash(),
        ]);
    }

    public function deleteAuditLog(Request $request, array $params): void
    {
        $this->guardCsrf($request, '/admin/logs/audit');
        $id = (int) ($params['id'] ?? 0);
        if ($id > 0) {
            $this->auditLogs->deleteById($id);
            $this->flash('success', 'Audit log entry deleted successfully.');
        }
        $this->redirect('/admin/logs/audit');
    }

    public function bulkDeleteAuditLogs(Request $request): void
    {
        $this->guardCsrf($request, '/admin/logs/audit');
        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        if (is_array($ids) && !empty($ids)) {
            $deleted = $this->auditLogs->deleteByIds($ids);
            $this->flash('success', "{$deleted} audit log entries deleted successfully.");
        } else {
            $this->flash('error', 'No audit log entries selected for deletion.');
        }
        $this->redirect('/admin/logs/audit');
    }

    public function purgeAuditLogs(Request $request): void
    {
        $this->guardCsrf($request, '/admin/logs/audit');
        $purgeType = (string) $request->input('purge_type', 'days');
        $days      = (int) $request->input('days', 90);

        if ($purgeType === 'all') {
            $deleted = $this->auditLogs->clearAll();
            $this->flash('success', "All {$deleted} audit log entries have been permanently cleared.");
        } else {
            $days = max(1, $days);
            $deleted = $this->auditLogs->deleteOlderThan($days);
            $this->flash('success', "{$deleted} audit log entries older than {$days} days deleted.");
        }

        $this->redirect('/admin/logs/audit');
    }

    private function guardCsrf(Request $request, string $redirect): void
    {
        if (!Csrf::verify((string) $request->input('_csrf'))) {
            $this->flash('error', 'Invalid session or CSRF token expired. Please try again.');
            $this->redirect($redirect);
        }
    }
}
