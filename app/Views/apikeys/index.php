<?php
use App\Core\View;
/** @var array<int,array<string,mixed>> $keys */
/** @var array<string,string>|null $newKey */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">API Keys</h1>
</div>

<?php if (!empty($newKey)): ?>
<div class="alert alert-warning">
    <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Copy your secret now — it will not be shown again.</div>
    <div class="mb-1"><span class="text-muted small">API Key</span><br><code><?= View::e($newKey['api_key']) ?></code></div>
    <div><span class="text-muted small">Secret Key</span><br><code><?= View::e($newKey['secret_key']) ?></code></div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Create API Key</div>
            <div class="card-body">
                <form method="post" action="<?= $base ?>/admin/apikeys">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                    <div class="mb-3">
                        <label class="form-label">Name / Label</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. WHMCS Production" required>
                    </div>
                    <button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Generate Key</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>API Key</th><th>Status</th><th>Last Used</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($keys)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No API keys.</td></tr>
                    <?php else: foreach ($keys as $k): ?>
                        <tr>
                            <td><?= View::e($k['name']) ?></td>
                            <td><code class="small"><?= View::e($k['api_key']) ?></code></td>
                            <td><span class="badge text-bg-<?= $k['status'] === 'active' ? 'success' : 'secondary' ?>"><?= View::e($k['status']) ?></span></td>
                            <td class="small"><?= View::e($k['last_used_at'] ?? 'Never') ?></td>
                            <td class="text-end text-nowrap">
                                <?php if ($k['status'] === 'active'): ?>
                                    <form method="post" action="<?= $base ?>/admin/apikeys/<?= (int) $k['id'] ?>/revoke" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                        <button class="btn btn-sm btn-outline-warning">Revoke</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?= $base ?>/admin/apikeys/<?= (int) $k['id'] ?>/activate" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                        <button class="btn btn-sm btn-outline-success">Activate</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= $base ?>/admin/apikeys/<?= (int) $k['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this API key?');">
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
    </div>
</div>
