<?php
use App\Core\View;
/** @var array<string,mixed> $license */
/** @var array<string,mixed>|null $product */
/** @var array<int,array<string,mixed>> $activations */

$statusBadge = static fn(string $s): string => match ($s) {
    'active' => 'success', 'suspended' => 'warning',
    'expired' => 'secondary', 'terminated' => 'danger', default => 'light',
};
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">License Detail</h1>
    <a href="<?= $base ?>/admin/licenses" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <code class="fs-5"><?= View::e($license['license_key']) ?></code>
                    <span class="badge text-bg-<?= $statusBadge($license['status']) ?>"><?= View::e($license['status']) ?></span>
                </div>
                <dl class="row small mb-0">
                    <dt class="col-5">Product</dt><dd class="col-7"><?= View::e($product['product_name'] ?? '-') ?></dd>
                    <dt class="col-5">Customer</dt><dd class="col-7"><?= View::e($license['customer_name'] ?? '-') ?></dd>
                    <dt class="col-5">Email</dt><dd class="col-7"><?= View::e($license['customer_email'] ?? '-') ?></dd>
                    <dt class="col-5">Domain</dt><dd class="col-7"><?= View::e($license['domain'] ?? '-') ?></dd>
                    <dt class="col-5">IP Address</dt><dd class="col-7"><?= View::e($license['ip_address'] ?? '-') ?></dd>
                    <dt class="col-5">Domain Lock</dt><dd class="col-7"><?= ((int) $license['domain_lock']) ? 'Yes' : 'No' ?></dd>
                    <dt class="col-5">IP Lock</dt><dd class="col-7"><?= ((int) $license['ip_lock']) ? 'Yes' : 'No' ?></dd>
                    <dt class="col-5">Activations</dt><dd class="col-7"><?= (int) $license['activation_count'] ?> / <?= (int) $license['activation_limit'] ?></dd>
                    <dt class="col-5">Expiry</dt><dd class="col-7"><?= View::e($license['expiry_date'] ?? 'Never') ?></dd>
                    <dt class="col-5">Created</dt><dd class="col-7"><?= View::e($license['created_at']) ?></dd>
                </dl>
            </div>
            <div class="card-footer bg-white d-flex flex-wrap gap-2">
                <?php if ($license['status'] === 'active'): ?>
                    <button class="btn btn-sm btn-warning elms-action" data-action="suspend" data-id="<?= (int) $license['id'] ?>">Suspend</button>
                <?php elseif ($license['status'] === 'suspended'): ?>
                    <button class="btn btn-sm btn-success elms-action" data-action="unsuspend" data-id="<?= (int) $license['id'] ?>">Unsuspend</button>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-secondary elms-action" data-action="reset" data-id="<?= (int) $license['id'] ?>">Reset</button>
                <button class="btn btn-sm btn-outline-danger elms-action" data-action="terminate" data-id="<?= (int) $license['id'] ?>">Terminate</button>
                <div class="input-group input-group-sm" style="max-width:260px">
                    <input type="date" id="renewDate" class="form-control">
                    <button class="btn btn-outline-primary elms-renew" data-id="<?= (int) $license['id'] ?>">Renew</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-hdd-stack me-1"></i>Activation History</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Domain</th><th>IP</th><th>Hostname</th><th>Status</th><th>Activated</th><th>Last Check</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($activations)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No activations yet.</td></tr>
                    <?php else: foreach ($activations as $a): ?>
                        <tr>
                            <td class="small"><?= View::e($a['domain'] ?? '-') ?></td>
                            <td class="small"><?= View::e($a['ip'] ?? '-') ?></td>
                            <td class="small"><?= View::e($a['server_hostname'] ?? '-') ?></td>
                            <td><span class="badge text-bg-<?= $a['status'] === 'active' ? 'success' : 'secondary' ?>"><?= View::e($a['status']) ?></span></td>
                            <td class="small"><?= View::e($a['activated_at']) ?></td>
                            <td class="small"><?= View::e($a['last_check'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="csrf-token" value="<?= View::e($csrf) ?>">
<script>window.ELMS_CSRF = document.getElementById('csrf-token').value;</script>
