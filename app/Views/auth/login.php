<?php
use App\Core\View;
/** @var array<string,string> $flash */
?>
<div class="text-center mb-4">
    <div class="elms-login-logo"><i class="bi bi-shield-lock"></i></div>
    <h1 class="h4 mt-3 mb-1">External License Manager</h1>
    <p class="text-muted small mb-0">Admin sign in</p>
</div>

<?php if (!empty($flash['error'])): ?>
    <div class="alert alert-danger py-2"><?= View::e($flash['error']) ?></div>
<?php endif; ?>

<form method="post" action="<?= $base ?>/admin/login" autocomplete="off">
    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
    <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i>Sign in</button>
</form>
