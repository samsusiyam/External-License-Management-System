<?php
use App\Core\View;
/** @var array<string,int> $stats */
/** @var array<int,array<string,mixed>> $recent */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Dashboard</h1>
    <a href="<?= $base ?>/admin/licenses/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New License</a>
</div>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total Licenses', $stats['total'], 'bi-key', 'primary'],
        ['Active', $stats['active'], 'bi-check-circle', 'success'],
        ['Suspended', $stats['suspended'], 'bi-pause-circle', 'warning'],
        ['Expired', $stats['expired'], 'bi-hourglass-bottom', 'secondary'],
        ['Terminated', $stats['terminated'], 'bi-x-octagon', 'danger'],
    ];
    foreach ($cards as [$label, $value, $icon, $color]): ?>
        <div class="col-6 col-md-4 col-xl">
            <div class="card elms-stat h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small text-uppercase"><?= View::e($label) ?></div>
                            <div class="fs-3 fw-semibold"><?= (int) $value ?></div>
                        </div>
                        <span class="elms-stat-icon text-<?= $color ?>"><i class="bi <?= $icon ?>"></i></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">API Requests (24h)</div>
                <div class="fs-4 fw-semibold"><?= (int) $stats['api_24h'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Failed Requests (24h)</div>
                <div class="fs-4 fw-semibold text-danger"><?= (int) $stats['api_failed_24h'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Products</div>
                <div class="fs-4 fw-semibold"><?= (int) $stats['products'] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-journal-text me-1"></i>Recent Activity</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr><th>Time</th><th>Actor</th><th>Action</th><th>Entity</th><th>IP</th></tr>
            </thead>
            <tbody>
            <?php if (empty($recent)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No activity yet.</td></tr>
            <?php else: foreach ($recent as $r): ?>
                <tr>
                    <td class="text-nowrap small"><?= View::e($r['created_at']) ?></td>
                    <td class="small"><span class="badge text-bg-light"><?= View::e($r['actor_type']) ?></span> <?= View::e($r['actor_id'] ?? '') ?></td>
                    <td class="small"><?= View::e($r['action']) ?></td>
                    <td class="small"><?= View::e(($r['entity_type'] ?? '') . ' ' . ($r['entity_id'] ?? '')) ?></td>
                    <td class="small text-muted"><?= View::e($r['ip'] ?? '') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
