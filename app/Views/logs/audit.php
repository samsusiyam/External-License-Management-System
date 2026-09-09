<?php
use App\Core\View;

/** @var array<int,array<string,mixed>> $logs */
/** @var int $page */
/** @var int $perPage */
/** @var int $total */
/** @var int $pages */
/** @var string $actor */
/** @var string $search */
/** @var string $csrf */

$actorBadge = static function (string $type): string {
    return match (strtolower($type)) {
        'admin'  => 'primary',
        'api'    => 'info',
        'system' => 'secondary',
        default  => 'light',
    };
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Audit Logs</h1>
        <div class="text-muted small">
            Total activity events: <strong><?= number_format($total) ?></strong>
            &middot; Page <strong><?= $page ?></strong> of <strong><?= max(1, $pages) ?></strong>
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Manual Purge Dropdown/Modal Trigger -->
        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#purgeAuditModal">
            <i class="bi bi-trash3 me-1"></i> Purge Audit Logs
        </button>

        <!-- Bulk Delete Trigger (hidden until selected) -->
        <button type="button" id="btnBulkDeleteAudit" class="btn btn-sm btn-danger d-none" onclick="submitBulkDeleteAudit()">
            <i class="bi bi-trash me-1"></i> Delete Selected (<span id="selectedCountAudit">0</span>)
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search action, actor, entity, details, IP..." value="<?= View::e($search) ?>">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="actor" class="form-select form-select-sm">
                    <option value="">All Actors (Admin, API, System)</option>
                    <option value="admin" <?= $actor === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="api" <?= $actor === 'api' ? 'selected' : '' ?>>API Client</option>
                    <option value="system" <?= $actor === 'system' ? 'selected' : '' ?>>System</option>
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
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <?php if ($search !== '' || $actor !== '' || $perPage !== 30): ?>
                    <a href="<?= $base ?>/admin/logs/audit" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
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
                        <input type="checkbox" class="form-check-input" id="checkAllAudit" onchange="toggleSelectAllAudit(this)">
                    </th>
                    <th style="width: 140px;">Time</th>
                    <th style="width: 140px;">Actor</th>
                    <th style="width: 150px;">Action</th>
                    <th style="width: 130px;">Entity</th>
                    <th>Details</th>
                    <th style="width: 110px;">IP</th>
                    <th style="width: 80px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary"></i>
                        No audit log entries found.
                    </td>
                </tr>
            <?php else: foreach ($logs as $l): ?>
                <tr>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input row-checkbox-audit" value="<?= (int) $l['id'] ?>" onchange="updateSelectedAuditState()">
                    </td>
                    <td class="small text-nowrap text-muted"><?= View::e($l['created_at']) ?></td>
                    <td class="small">
                        <span class="badge text-bg-<?= $actorBadge($l['actor_type'] ?? '') ?>"><?= View::e($l['actor_type']) ?></span>
                        <span class="fw-semibold ms-1"><?= View::e($l['actor_id'] ?? '') ?></span>
                    </td>
                    <td class="small">
                        <code class="text-dark fw-bold"><?= View::e($l['action']) ?></code>
                    </td>
                    <td class="small text-muted">
                        <?= View::e(trim(($l['entity_type'] ?? '') . ' ' . ($l['entity_id'] ?? ''))) ?: '-' ?>
                    </td>
                    <td class="small">
                        <span class="text-truncate d-inline-block font-monospace" style="max-width: 320px;" title="<?= View::e($l['details'] ?? '') ?>">
                            <?= View::e($l['details'] ?? '-') ?>
                        </span>
                    </td>
                    <td class="small text-muted font-monospace"><?= View::e($l['ip'] ?? '-') ?></td>
                    <td class="text-end text-nowrap">
                        <form method="post" action="<?= $base ?>/admin/logs/audit/<?= (int) $l['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this audit log entry?');">
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
<form id="bulkDeleteAuditForm" method="post" action="<?= $base ?>/admin/logs/audit/delete" style="display: none;">
    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
    <input type="hidden" name="ids" id="bulkDeleteAuditIds" value="">
</form>

<!-- Pagination -->
<?php if (($pages ?? 1) > 1): ?>
<?php
    $queryParams = [];
    if ($actor !== '') $queryParams['actor'] = $actor;
    if ($search !== '') $queryParams['search'] = $search;
    if ($perPage !== 30) $queryParams['per_page'] = $perPage;

    $buildUrl = function ($p) use ($queryParams, $base) {
        $q = array_merge($queryParams, ['page' => $p]);
        return $base . '/admin/logs/audit?' . http_build_query($q);
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

<!-- Modal: Purge Audit Logs -->
<div class="modal fade" id="purgeAuditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= $base ?>/admin/logs/audit/purge" onsubmit="return confirm('Are you sure you want to proceed with purging audit logs?');">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><i class="bi bi-trash3 text-warning me-2"></i>Purge Audit Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Choose how you want to purge audit trail logs:</p>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="purge_type" id="purgeAuditDays" value="days" checked onchange="toggleAuditPurgeDays(true)">
                        <label class="form-check-label fw-semibold" for="purgeAuditDays">
                            Delete audit logs older than a specific number of days
                        </label>
                        <div class="mt-2" id="purgeAuditDaysWrapper">
                            <div class="input-group input-group-sm" style="max-width: 220px;">
                                <input type="number" name="days" class="form-control" value="90" min="1" max="1000">
                                <span class="input-group-text">days old</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="purge_type" id="purgeAuditAll" value="all" onchange="toggleAuditPurgeDays(false)">
                        <label class="form-check-label fw-semibold text-danger" for="purgeAuditAll">
                            Clear ALL Audit logs
                        </label>
                        <div class="small text-muted">Permanently wipes all recorded administrator and system activity logs.</div>
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

<script>
function toggleSelectAllAudit(master) {
    const checkboxes = document.querySelectorAll('.row-checkbox-audit');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateSelectedAuditState();
}

function updateSelectedAuditState() {
    const checkboxes = document.querySelectorAll('.row-checkbox-audit:checked');
    const count = checkboxes.length;
    const btn = document.getElementById('btnBulkDeleteAudit');
    const counter = document.getElementById('selectedCountAudit');
    if (counter) counter.textContent = count;
    if (btn) {
        if (count > 0) {
            btn.classList.remove('d-none');
        } else {
            btn.classList.add('d-none');
        }
    }
}

function submitBulkDeleteAudit() {
    const checkboxes = document.querySelectorAll('.row-checkbox-audit:checked');
    if (checkboxes.length === 0) return;
    if (!confirm(`Are you sure you want to permanently delete the ${checkboxes.length} selected audit log entries?`)) return;

    const ids = Array.from(checkboxes).map(cb => cb.value);
    document.getElementById('bulkDeleteAuditIds').value = ids.join(',');
    document.getElementById('bulkDeleteAuditForm').submit();
}

function toggleAuditPurgeDays(show) {
    const wrapper = document.getElementById('purgeAuditDaysWrapper');
    if (wrapper) wrapper.style.display = show ? 'block' : 'none';
}
</script>
