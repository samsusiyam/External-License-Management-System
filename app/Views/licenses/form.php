<?php
use App\Core\View;
/** @var array<int,array<string,mixed>> $products */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create License</h1>
    <a href="<?= $base ?>/admin/licenses" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= $base ?>/admin/licenses">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Select product...</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= View::e($p['product_name']) ?> (<?= View::e($p['product_key']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control">
                    <div class="form-text">Leave blank for a perpetual (lifetime) license.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Customer Name</label>
                    <input type="text" name="customer_name" class="form-control" maxlength="150" placeholder="e.g. John Doe">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Customer Email</label>
                    <input type="email" name="customer_email" class="form-control" placeholder="e.g. john@example.com">
                </div>

                <!-- Domain and IP Bindings -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Domain Name</label>
                    <input type="text" name="domain" id="domainInput" class="form-control" placeholder="e.g. clientdomain.com">
                    <div class="form-text">Optional. Bound domain name for license verification.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">IP Address</label>
                    <input type="text" name="ip_address" id="ipInput" class="form-control" placeholder="e.g. 192.168.1.1">
                    <div class="form-text">Optional. Bound server IP address.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Activation Limit</label>
                    <input type="number" name="activation_limit" class="form-control" value="1" min="1">
                </div>
                <div class="col-md-4 d-flex align-items-center pt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="domain_lock" value="1" id="dl" checked>
                        <label class="form-check-label fw-semibold" for="dl">Enforce Domain Lock</label>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-center pt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ip_lock" value="1" id="il">
                        <label class="form-check-label fw-semibold" for="il">Enforce IP Lock</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Notes / Reference</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional internal notes..."></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Generate License</button>
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
