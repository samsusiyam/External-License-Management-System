<?php
use App\Core\View;
/** @var array<int,array<string,mixed>> $products */
/** @var array<string,mixed>|null $license */
$isEdit = !empty($license['id']);
$actionUrl = $isEdit ? ($base . '/admin/licenses/' . (int) $license['id'] . '/update') : ($base . '/admin/licenses');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?= $isEdit ? 'Edit License' : 'Create License' ?></h1>
        <p class="text-muted mb-0"><?= $isEdit ? ('Updating license configuration for <code>' . View::e($license['license_key']) . '</code>') : 'Issue a new license with domain lock, activation limits, and expiry dates.' ?></p>
    </div>
    <a href="<?= $isEdit ? ($base . '/admin/licenses/' . (int) $license['id']) : ($base . '/admin/licenses') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post" action="<?= $actionUrl ?>">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            
            <?php if ($isEdit): ?>
                <div class="alert alert-light border d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <span class="text-muted small">License Key:</span>
                        <div class="fs-5 font-monospace fw-bold text-dark"><?= View::e($license['license_key']) ?></div>
                    </div>
                    <div>
                        <span class="badge bg-secondary"><?= View::e($license['status']) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Select product...</option>
                        <?php foreach ($products as $p): ?>
                            <?php $selected = ($isEdit && (int) $license['product_id'] === (int) $p['id']) ? 'selected' : ''; ?>
                            <option value="<?= (int) $p['id'] ?>" <?= $selected ?>>
                                <?= View::e($p['product_name']) ?> (<?= View::e($p['product_key']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control" value="<?= View::e($license['expiry_date'] ?? '') ?>">
                    <div class="form-text">Leave blank for a perpetual (lifetime) license.</div>
                </div>

                <?php if ($isEdit): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['active' => 'Active', 'suspended' => 'Suspended', 'expired' => 'Expired', 'terminated' => 'Terminated'] as $stKey => $stLbl): ?>
                                <option value="<?= $stKey ?>" <?= ($license['status'] ?? '') === $stKey ? 'selected' : '' ?>>
                                    <?= $stLbl ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">WHMCS Service ID</label>
                        <input type="number" name="whmcs_service_id" class="form-control" value="<?= View::e($license['whmcs_service_id'] ?? '') ?>" placeholder="e.g. 42">
                    </div>
                <?php endif; ?>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Customer Name</label>
                    <input type="text" name="customer_name" class="form-control" maxlength="150" placeholder="e.g. John Doe" value="<?= View::e($license['customer_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Customer Email</label>
                    <input type="email" name="customer_email" class="form-control" placeholder="e.g. john@example.com" value="<?= View::e($license['customer_email'] ?? '') ?>">
                </div>

                <!-- Domain and IP Bindings -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Domain Name</label>
                    <input type="text" name="domain" id="domainInput" class="form-control" placeholder="e.g. clientdomain.com" value="<?= View::e($license['domain'] ?? '') ?>">
                    <div class="form-text">Optional. Bound domain name for license verification.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">IP Address</label>
                    <input type="text" name="ip_address" id="ipInput" class="form-control" placeholder="e.g. 192.168.1.1" value="<?= View::e($license['ip_address'] ?? '') ?>">
                    <div class="form-text">Optional. Bound server IP address.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Activation Limit</label>
                    <input type="number" name="activation_limit" class="form-control" value="<?= (int) ($license['activation_limit'] ?? 1) ?>" min="1">
                </div>
                <div class="col-md-4 d-flex align-items-center pt-3">
                    <div class="form-check">
                        <?php $dlChecked = $isEdit ? ((int) ($license['domain_lock'] ?? 0) === 1) : true; ?>
                        <input class="form-check-input" type="checkbox" name="domain_lock" value="1" id="dl" <?= $dlChecked ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="dl">Enforce Domain Lock</label>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-center pt-3">
                    <div class="form-check">
                        <?php $ilChecked = $isEdit ? ((int) ($license['ip_lock'] ?? 0) === 1) : false; ?>
                        <input class="form-check-input" type="checkbox" name="ip_lock" value="1" id="il" <?= $ilChecked ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="il">Enforce IP Lock</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Notes / Reference</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional internal notes..."><?= View::e($license['notes'] ?? '') ?></textarea>
                </div>
            </div>
            
            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi <?= $isEdit ? 'bi-check-circle' : 'bi-check-lg' ?> me-1"></i>
                    <?= $isEdit ? 'Save Changes' : 'Generate License' ?>
                </button>
                <a href="<?= $isEdit ? ($base . '/admin/licenses/' . (int) $license['id']) : ($base . '/admin/licenses') ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const domainInput = document.getElementById('domainInput');
    const dl = document.getElementById('dl');
    const ipInput = document.getElementById('ipInput');
    const il = document.getElementById('il');

    domainInput.addEventListener('input', function() {
        if (this.value.trim() !== '') {
            dl.checked = true;
        }
    });

    ipInput.addEventListener('input', function() {
        if (this.value.trim() !== '') {
            il.checked = true;
        }
    });
});
</script>
