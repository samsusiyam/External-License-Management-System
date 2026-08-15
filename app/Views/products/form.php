<?php
use App\Core\View;
/** @var array<string,mixed>|null $product */
$p = $product ?? null;
$action = $p ? $base . '/admin/products/' . (int) $p['id'] . '/update' : $base . '/admin/products';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= $p ? 'Edit' : 'Add' ?> Product</h1>
    <a href="<?= $base ?>/admin/products" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= View::e($action) ?>">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="product_name" class="form-control" required maxlength="150" value="<?= View::e($p['product_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Product Key <span class="text-danger">*</span></label>
                    <input type="text" name="product_key" class="form-control" required maxlength="80" placeholder="e.g. WHMCS-OTP" value="<?= View::e($p['product_key'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= View::e($p['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Latest Version</label>
                    <input type="text" name="latest_version" class="form-control" placeholder="1.0.0" value="<?= View::e($p['latest_version'] ?? '') ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Download URL</label>
                    <input type="url" name="download_url" class="form-control" value="<?= View::e($p['download_url'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Update Notes</label>
                    <textarea name="update_notes" class="form-control" rows="2"><?= View::e($p['update_notes'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($p['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($p['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Product</button>
            </div>
        </form>
    </div>
</div>
