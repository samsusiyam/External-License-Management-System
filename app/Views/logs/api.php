<?php
use App\Core\View;
/** @var array<int,array<string,mixed>> $logs */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">API Logs</h1>
    <div class="btn-group">
        <a href="<?= $base ?>/admin/logs/api" class="btn btn-sm btn-outline-secondary <?= !$failed ? 'active' : '' ?>">All</a>
        <a href="<?= $base ?>/admin/logs/api?failed=1" class="btn btn-sm btn-outline-danger <?= $failed ? 'active' : '' ?>">Failed only</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Time</th><th>Endpoint</th><th>Method</th><th>Status</th><th>IP</th><th>ms</th><th>Response</th></tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No log entries.</td></tr>
            <?php else: foreach ($logs as $l): ?>
                <tr>
                    <td class="small text-nowrap"><?= View::e($l['created_at']) ?></td>
                    <td class="small"><code><?= View::e($l['endpoint']) ?></code></td>
                    <td class="small"><?= View::e($l['method']) ?></td>
                    <td><span class="badge text-bg-<?= (int) $l['success'] === 1 ? 'success' : 'danger' ?>"><?= (int) $l['status_code'] ?></span></td>
                    <td class="small"><?= View::e($l['ip'] ?? '-') ?></td>
                    <td class="small"><?= (int) $l['duration_ms'] ?></td>
                    <td class="small"><span class="text-truncate d-inline-block" style="max-width:280px" title="<?= View::e($l['response_body'] ?? '') ?>"><?= View::e($l['response_body'] ?? '') ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($pages ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?><?= $failed ? '&failed=1' : '' ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
