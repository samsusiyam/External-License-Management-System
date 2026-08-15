<?php
use App\Core\View;
/** @var array<int,array<string,mixed>> $licenses */
/** @var array<int,array<string,mixed>> $products */
/** @var array<string,mixed> $filters */

$statusBadge = static function (string $s): string {
    return match ($s) {
        'active'     => 'success',
        'suspended'  => 'warning',
        'expired'    => 'secondary',
        'terminated' => 'danger',
        default      => 'light',
    };
};
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Licenses</h1>
    <a href="<?= $base ?>/admin/licenses/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New License</a>
</div>

<form class="card border-0 shadow-sm mb-3" method="get">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">Search</label>
            <input type="text" name="search" value="<?= View::e($filters['search']) ?>" class="form-control" placeholder="Key, customer, domain...">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select">
                <option value="">All</option>
                <?php foreach (['active','suspended','expired','terminated'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Product</label>
            <select name="product_id" class="form-select">
                <option value="">All</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (string) $filters['product_id'] === (string) $p['id'] ? 'selected' : '' ?>><?= View::e($p['product_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>License Key</th><th>Product</th><th>Customer</th><th>Domain</th>
                    <th>Status</th><th>Expiry</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($licenses)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No licenses found.</td></tr>
            <?php else: foreach ($licenses as $l): ?>
                <tr data-license-row="<?= (int) $l['id'] ?>">
                    <td><code><?= View::e($l['license_key']) ?></code></td>
                    <td class="small"><?= View::e($l['product_name'] ?? '-') ?></td>
                    <td class="small"><?= View::e($l['customer_name'] ?? '-') ?><br><span class="text-muted"><?= View::e($l['customer_email'] ?? '') ?></span></td>
                    <td class="small"><?= View::e($l['domain'] ?? '-') ?></td>
                    <td><span class="badge text-bg-<?= $statusBadge($l['status']) ?>"><?= View::e($l['status']) ?></span></td>
                    <td class="small"><?= View::e($l['expiry_date'] ?? 'Never') ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= $base ?>/admin/licenses/<?= (int) $l['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-gear"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($l['status'] === 'active'): ?>
                                    <li><a class="dropdown-item elms-action" data-action="suspend" data-id="<?= (int) $l['id'] ?>" href="#">Suspend</a></li>
                                <?php elseif ($l['status'] === 'suspended'): ?>
                                    <li><a class="dropdown-item elms-action" data-action="unsuspend" data-id="<?= (int) $l['id'] ?>" href="#">Unsuspend</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item elms-action" data-action="reset" data-id="<?= (int) $l['id'] ?>" href="#">Reset Bindings</a></li>
                                <li><a class="dropdown-item text-warning elms-action" data-action="terminate" data-id="<?= (int) $l['id'] ?>" href="#">Terminate</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger elms-action" data-action="delete" data-id="<?= (int) $l['id'] ?>" href="#">Delete</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($pages ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm">
        <?php for ($i = 1; $i <= $pages; $i++):
            $q = http_build_query(array_merge($filters, ['page' => $i])); ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= View::e($q) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<input type="hidden" id="csrf-token" value="<?= View::e($csrf) ?>">
<script>window.ELMS_CSRF = document.getElementById('csrf-token').value;</script>
