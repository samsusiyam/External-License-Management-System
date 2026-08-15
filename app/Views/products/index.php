<?php
use App\Core\View;
/** @var array<int,array<string,mixed>> $products */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Products</h1>
    <a href="<?= $base ?>/admin/products/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Product</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Name</th><th>Product Key</th><th>Version</th><th>Status</th><th>Created</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No products yet.</td></tr>
            <?php else: foreach ($products as $p): ?>
                <tr>
                    <td><?= View::e($p['product_name']) ?></td>
                    <td><code><?= View::e($p['product_key']) ?></code></td>
                    <td class="small"><?= View::e($p['latest_version'] ?? '-') ?></td>
                    <td><span class="badge text-bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>"><?= View::e($p['status']) ?></span></td>
                    <td class="small"><?= View::e($p['created_at']) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= $base ?>/admin/products/<?= (int) $p['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= $base ?>/admin/products/<?= (int) $p['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this product and all its licenses?');">
                            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
