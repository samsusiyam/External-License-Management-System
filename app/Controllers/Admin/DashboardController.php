<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\License;
use App\Models\ApiLog;
use App\Models\AuditLog;
use App\Models\Product;

/**
 * DashboardController
 *
 * Overview metrics: license counts, API request stats, recent activity.
 */
class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $licenses = new License();
        $licenses->expireOverdue();
        $apiLogs  = new ApiLog();

        $counts = $licenses->statusCounts();
        $total  = array_sum($counts);

        $stats = [
            'total'        => $total,
            'active'       => $counts['active'],
            'suspended'    => $counts['suspended'],
            'expired'      => $counts['expired'],
            'terminated'   => $counts['terminated'],
            'products'     => (new Product())->count(),
            'api_24h'      => $apiLogs->countSince(24),
            'api_failed_24h' => $apiLogs->countSince(24, true),
            'api_total'    => $apiLogs->totalCount(),
        ];

        $this->view('dashboard/index', [
            'title'  => 'Dashboard',
            'stats'  => $stats,
            'recent' => (new AuditLog())->recent(15),
            'flash'  => self::pullFlash(),
        ]);
    }
}
