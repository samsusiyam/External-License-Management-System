<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ApiLog;
use App\Models\AuditLog;

/**
 * LogController
 *
 * Read-only views for API logs and audit logs.
 */
class LogController extends Controller
{
    public function apiLogs(Request $request): void
    {
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = 30;
        $offset  = ($page - 1) * $perPage;
        $failed  = $request->query('failed') === '1' ? true : null;

        $model = new ApiLog();
        $rows  = $model->paginate($perPage, $offset, $failed);
        $total = $model->totalCount($failed);

        $this->view('logs/api', [
            'title'   => 'API Logs',
            'logs'    => $rows,
            'page'    => $page,
            'perPage' => $perPage,
            'total'   => $total,
            'pages'   => (int) ceil($total / $perPage),
            'failed'  => $failed === true,
            'flash'   => self::pullFlash(),
        ]);
    }

    public function auditLogs(Request $request): void
    {
        $this->view('logs/audit', [
            'title' => 'Audit Logs',
            'logs'  => (new AuditLog())->recent(200),
            'flash' => self::pullFlash(),
        ]);
    }
}
