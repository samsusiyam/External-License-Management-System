<?php
use App\Core\View;

/** @var array<int,array<string,mixed>> $logs */
/** @var int $page */
/** @var int $perPage */
/** @var int $total */
/** @var int $pages */
/** @var bool $failed */
/** @var string $search */
/** @var string $method */
/** @var string $csrf */
/** @var array<string,mixed> $settings */

$retentionDays = (int) ($settings['api_log_retention_days'] ?? 30);
$autoPruneEnabled = !empty($settings['auto_prune_api_logs']);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">API Logs</h1>
        <div class="text-muted small">
            Total entries: <strong><?= number_format($total) ?></strong>
            &middot; Page <strong><?= $page ?></strong> of <strong><?= max(1, $pages) ?></strong>
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Auto Delete Settings Trigger -->
        <button type="button" class="btn btn-sm <?= ($autoPruneEnabled && $retentionDays > 0) ? 'btn-outline-success' : 'btn-outline-secondary' ?>" data-bs-toggle="modal" data-bs-target="#autoDeleteModal">
            <i class="bi bi-clock-history me-1"></i>
            Auto-Delete: <strong><?= ($autoPruneEnabled && $retentionDays > 0) ? "{$retentionDays} Days" : 'Disabled' ?></strong>
        </button>

        <!-- Manual Purge Dropdown/Modal Trigger -->
        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#purgeLogsModal">
            <i class="bi bi-trash3 me-1"></i> Purge Logs
        </button>

        <!-- Bulk Delete Trigger (hidden until selected) -->
        <button type="button" id="btnBulkDelete" class="btn btn-sm btn-danger d-none" onclick="submitBulkDelete()">
            <i class="bi bi-trash me-1"></i> Delete Selected (<span id="selectedCount">0</span>)
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search endpoint, IP, status..." value="<?= View::e($search) ?>">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="method" class="form-select form-select-sm">
                    <option value="">All Methods</option>
                    <option value="POST" <?= $method === 'POST' ? 'selected' : '' ?>>POST</option>
                    <option value="GET" <?= $method === 'GET' ? 'selected' : '' ?>>GET</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="failed" class="form-select form-select-sm">
                    <option value="0" <?= !$failed ? 'selected' : '' ?>>All Statuses</option>
                    <option value="1" <?= $failed ? 'selected' : '' ?>>Failed Only</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="per_page" class="form-select form-select-sm">
                    <option value="15" <?= $perPage === 15 ? 'selected' : '' ?>>15 per page</option>
                    <option value="30" <?= $perPage === 30 ? 'selected' : '' ?>>30 per page</option>
                    <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50 per page</option>
                    <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100 per page</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <?php if ($search !== '' || $method !== '' || $failed || $perPage !== 30): ?>
                    <a href="<?= $base ?>/admin/logs/api" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Main Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 36px;" class="text-center">
                        <input type="checkbox" class="form-check-input" id="checkAll" onchange="toggleSelectAll(this)">
                    </th>
                    <th style="width: 140px;">Time</th>
                    <th>Endpoint</th>
                    <th style="width: 80px;">Method</th>
                    <th style="width: 90px;">Status</th>
                    <th style="width: 120px;">IP</th>
                    <th style="width: 70px;">ms</th>
                    <th>Response Preview</th>
                    <th style="width: 90px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                        No API log entries found.
                    </td>
                </tr>
            <?php else: foreach ($logs as $l): ?>
                <tr>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input row-checkbox" value="<?= (int) $l['id'] ?>" onchange="updateSelectedState()">
                    </td>
                    <td class="small text-nowrap text-muted"><?= View::e($l['created_at']) ?></td>
                    <td class="small">
                        <code class="text-dark fw-semibold"><?= View::e($l['endpoint']) ?></code>
                    </td>
                    <td class="small">
                        <span class="badge text-bg-<?= $l['method'] === 'POST' ? 'primary' : 'secondary' ?>"><?= View::e($l['method']) ?></span>
                    </td>
                    <td>
                        <span class="badge text-bg-<?= (int) $l['success'] === 1 ? 'success' : 'danger' ?>">
                            <?= (int) $l['status_code'] ?> <?= (int) $l['success'] === 1 ? 'OK' : 'ERR' ?>
                        </span>
                    </td>
                    <td class="small text-muted font-monospace"><?= View::e($l['ip'] ?? '-') ?></td>
                    <td class="small text-muted"><?= (int) $l['duration_ms'] ?></td>
                    <td class="small">
                        <div class="d-flex align-items-center gap-1">
                            <span class="text-truncate d-inline-block font-monospace" style="max-width: 260px;" title="<?= View::e($l['response_body'] ?? '') ?>">
                                <?= View::e($l['response_body'] ?? '') ?>
                            </span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-muted ms-1" onclick='showLogDetails(<?= json_encode($l, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="View Full Payload">
                                <i class="bi bi-arrows-angle-expand"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-end text-nowrap">
                        <form method="post" action="<?= $base ?>/admin/logs/api/<?= (int) $l['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this API log entry?');">
                            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2" title="Delete Log">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bulk Delete Form -->
<form id="bulkDeleteForm" method="post" action="<?= $base ?>/admin/logs/api/delete" style="display: none;">
    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
    <input type="hidden" name="ids" id="bulkDeleteIds" value="">
</form>

<!-- Pagination -->
<?php if (($pages ?? 1) > 1): ?>
<?php
    $queryParams = [];
    if ($failed) $queryParams['failed'] = '1';
    if ($search !== '') $queryParams['search'] = $search;
    if ($method !== '') $queryParams['method'] = $method;
    if ($perPage !== 30) $queryParams['per_page'] = $perPage;

    $buildUrl = function ($p) use ($queryParams, $base) {
        $q = array_merge($queryParams, ['page' => $p]);
        return $base . '/admin/logs/api?' . http_build_query($q);
    };

    $start = max(1, $page - 3);
    $end   = min($pages, $page + 3);
?>
<nav class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
    <div class="small text-muted">
        Showing <?= (($page - 1) * $perPage) + 1 ?> to <?= min($total, $page * $perPage) ?> of <?= number_format($total) ?> entries
    </div>
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $buildUrl(1) ?>">&laquo; First</a>
        </li>
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $buildUrl($page - 1) ?>">&lsaquo; Prev</a>
        </li>

        <?php if ($start > 1): ?>
            <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= $buildUrl($i) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
            <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
        <?php endif; ?>

        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $buildUrl($page + 1) ?>">Next &rsaquo;</a>
        </li>
        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $buildUrl($pages) ?>">Last &raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<!-- Modal: Auto Delete Settings -->
<div class="modal fade" id="autoDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= $base ?>/admin/logs/api/settings">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>API Logs Auto-Delete Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Automatically cleans up older API request/response logs in the background so your database stays fast and lightweight.
                    </p>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="autoPruneSwitch" name="auto_prune_api_logs" value="1" <?= $autoPruneEnabled ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="autoPruneSwitch">Enable Automatic Log Deletion</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Retention Period (Delete logs older than):</label>
                        <select name="api_log_retention_days" class="form-select">
                            <option value="3" <?= $retentionDays === 3 ? 'selected' : '' ?>>3 Days</option>
                            <option value="7" <?= $retentionDays === 7 ? 'selected' : '' ?>>7 Days (1 Week)</option>
                            <option value="15" <?= $retentionDays === 15 ? 'selected' : '' ?>>15 Days</option>
                            <option value="30" <?= $retentionDays === 30 ? 'selected' : '' ?>>30 Days (1 Month)</option>
                            <option value="60" <?= $retentionDays === 60 ? 'selected' : '' ?>>60 Days (2 Months)</option>
                            <option value="90" <?= $retentionDays === 90 ? 'selected' : '' ?>>90 Days (3 Months)</option>
                            <option value="180" <?= $retentionDays === 180 ? 'selected' : '' ?>>180 Days (6 Months)</option>
                            <option value="365" <?= $retentionDays === 365 ? 'selected' : '' ?>>365 Days (1 Year)</option>
                        </select>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="run_prune_now" value="1" id="pruneNowCheck">
                        <label class="form-check-label small text-secondary" for="pruneNowCheck">
                            Also run cleanup immediately upon saving
                        </label>
                    </div>

                    <?php if (!empty($settings['last_prune_at'])): ?>
                        <div class="alert alert-light border small text-muted mb-0 py-2">
                            <i class="bi bi-info-circle me-1"></i> Last automatic cleanup was performed at: <strong><?= View::e($settings['last_prune_at']) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i> Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Manual Purge Logs -->
<div class="modal fade" id="purgeLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= $base ?>/admin/logs/api/purge" onsubmit="return confirm('Are you sure you want to proceed with this log cleanup action?');">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><i class="bi bi-trash3 text-warning me-2"></i>Manual Log Cleanup / Purge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Choose how you want to purge existing API logs:</p>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="purge_type" id="purgeTypeDays" value="days" checked onchange="togglePurgeDaysInput(true)">
                        <label class="form-check-label fw-semibold" for="purgeTypeDays">
                            Delete logs older than a specific number of days
                        </label>
                        <div class="mt-2" id="purgeDaysWrapper">
                            <div class="input-group input-group-sm" style="max-width: 220px;">
                                <input type="number" name="days" class="form-control" value="30" min="1" max="1000">
                                <span class="input-group-text">days old</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="purge_type" id="purgeTypeFailed" value="failed" onchange="togglePurgeDaysInput(false)">
                        <label class="form-check-label fw-semibold text-danger" for="purgeTypeFailed">
                            Delete only Failed / Error logs
                        </label>
                        <div class="small text-muted">Keeps successful API verification logs intact and clears error logs.</div>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="purge_type" id="purgeTypeAll" value="all" onchange="togglePurgeDaysInput(false)">
                        <label class="form-check-label fw-semibold text-danger" for="purgeTypeAll">
                            Clear ALL API logs (Empty table)
                        </label>
                        <div class="small text-muted">Permanently wipes all historical API logs.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash3 me-1"></i> Proceed with Purge</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Log Details Viewer -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logModalTitle"><i class="bi bi-journal-code me-2"></i>API Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <div class="p-2 border rounded bg-light small">
                            <span class="text-muted d-block">Time:</span>
                            <span id="modalTime" class="fw-semibold font-monospace">-</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-2 border rounded bg-light small">
                            <span class="text-muted d-block">IP Address:</span>
                            <span id="modalIp" class="fw-semibold font-monospace">-</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-2 border rounded bg-light small">
                            <span class="text-muted d-block">Endpoint &amp; Method:</span>
                            <span id="modalEndpoint" class="fw-semibold font-monospace">-</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-2 border rounded bg-light small">
                            <span class="text-muted d-block">Status &amp; Duration:</span>
                            <span id="modalStatus" class="fw-semibold font-monospace">-</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Request Body</label>
                    <pre class="bg-dark text-light p-3 rounded small font-monospace overflow-auto" style="max-height: 200px;" id="modalRequestBody">-</pre>
                </div>

                <div>
                    <label class="form-label small fw-bold text-uppercase text-muted">Response Body</label>
                    <pre class="bg-dark text-light p-3 rounded small font-monospace overflow-auto" style="max-height: 250px;" id="modalResponseBody">-</pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateSelectedState();
}

function updateSelectedState() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const count = checkboxes.length;
    const btn = document.getElementById('btnBulkDelete');
    const counter = document.getElementById('selectedCount');
    if (counter) counter.textContent = count;
    if (btn) {
        if (count > 0) {
            btn.classList.remove('d-none');
        } else {
            btn.classList.add('d-none');
        }
    }
}

function submitBulkDelete() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) return;
    if (!confirm(`Are you sure you want to permanently delete the ${checkboxes.length} selected API log entries?`)) return;

    const ids = Array.from(checkboxes).map(cb => cb.value);
    document.getElementById('bulkDeleteIds').value = ids.join(',');
    document.getElementById('bulkDeleteForm').submit();
}

function togglePurgeDaysInput(show) {
    const wrapper = document.getElementById('purgeDaysWrapper');
    if (wrapper) wrapper.style.display = show ? 'block' : 'none';
}

function showLogDetails(log) {
    document.getElementById('modalTime').textContent = log.created_at || '-';
    document.getElementById('modalIp').textContent = log.ip || '-';
    document.getElementById('modalEndpoint').textContent = (log.method || '') + ' ' + (log.endpoint || '');
    document.getElementById('modalStatus').textContent = (log.status_code || '') + ' (' + (log.duration_ms || 0) + ' ms)';

    let reqPretty = log.request_body || '(empty)';
    try {
        const parsed = JSON.parse(reqPretty);
        reqPretty = JSON.stringify(parsed, null, 2);
    } catch(e) {}
    document.getElementById('modalRequestBody').textContent = reqPretty;

    let resPretty = log.response_body || '(empty)';
    try {
        const parsed = JSON.parse(resPretty);
        resPretty = JSON.stringify(parsed, null, 2);
    } catch(e) {}
    document.getElementById('modalResponseBody').textContent = resPretty;

    const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    modal.show();
}
</script>
