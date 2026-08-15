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
                    <label class="form-label">Product <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Select product...</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= View::e($p['product_name']) ?> (<?= View::e($p['product_key']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control">
                    <div class="form-text">Leave blank for a perpetual license.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Customer Name</label>
                    <input type="text" name="customer_name" class="form-control" maxlength="150">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Customer Email</label>
                    <input type="email" name="customer_email" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Activation Limit</label>
                    <input type="number" name="activation_limit" class="form-control" value="1" min="1">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="domain_lock" value="1" id="dl">
                        <label class="form-check-label" for="dl">Domain Lock</label>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ip_lock" value="1" id="il">
                        <label class="form-check-label" for="il">IP Lock</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Generate License</button>
            </div>
        </form>
    </div>
</div>
