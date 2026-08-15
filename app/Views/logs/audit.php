<?php
use App\Core\View;
/** @var array<int,array<string,mixed>> $logs */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Audit Logs</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Time</th><th>Actor</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th></tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No audit entries.</td></tr>
            <?php else: foreach ($logs as $l): ?>
                <tr>
                    <td class="small text-nowrap"><?= View::e($l['created_at']) ?></td>
                    <td class="small"><span class="badge text-bg-light"><?= View::e($l['actor_type']) ?></span> <?= View::e($l['actor_id'] ?? '') ?></td>
                    <td class="small"><?= View::e($l['action']) ?></td>
                    <td class="small"><?= View::e(trim(($l['entity_type'] ?? '') . ' ' . ($l['entity_id'] ?? ''))) ?: '-' ?></td>
                    <td class="small"><span class="text-truncate d-inline-block" style="max-width:320px" title="<?= View::e($l['details'] ?? '') ?>"><?= View::e($l['details'] ?? '') ?></span></td>
                    <td class="small text-muted"><?= View::e($l['ip'] ?? '-') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
