<?php
/** @var string $title */
/** @var string $content */
use App\Core\View;
use App\Core\Config;

$admin = $_SESSION['admin'] ?? null;
$path  = $_SERVER['REQUEST_URI'] ?? '';
$initials = '';
if (!empty($admin['name'])) {
    foreach (explode(' ', (string) $admin['name']) as $part) {
        $initials .= mb_substr($part, 0, 1);
    }
    $initials = strtoupper(substr($initials, 0, 2));
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'ELMS') ?> &middot; <?= View::e(Config::get('app.name', 'ELMS')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="elms-layout">
    <!-- Sidebar -->
    <aside class="elms-sidebar" id="elmsSidebar">
        <div class="elms-brand">
            <div class="elms-brand-logo"><i class="bi bi-shield-lock"></i></div>
            <div>
                <div class="elms-brand-name">ELMS</div>
                <div class="elms-brand-sub">License Console</div>
            </div>
        </div>
        <nav class="nav flex-column">
            <div class="elms-nav-label">Overview</div>
            <a class="nav-link <?= (rtrim($path, '/') === $base . '/admin') ? 'active' : '' ?>" href="<?= $base ?>/admin"><i class="bi bi-speedometer2"></i>Dashboard</a>
            <a class="nav-link <?= str_contains($path, '/admin/licenses') ? 'active' : '' ?>" href="<?= $base ?>/admin/licenses"><i class="bi bi-key"></i>Licenses</a>
            <a class="nav-link <?= str_contains($path, '/admin/products') ? 'active' : '' ?>" href="<?= $base ?>/admin/products"><i class="bi bi-box-seam"></i>Products</a>
            <div class="elms-nav-label">System</div>
            <a class="nav-link <?= str_contains($path, '/admin/tester') ? 'active' : '' ?>" href="<?= $base ?>/admin/tester"><i class="bi bi-play-circle"></i>API Tester</a>
            <a class="nav-link <?= str_contains($path, '/admin/apikeys') ? 'active' : '' ?>" href="<?= $base ?>/admin/apikeys"><i class="bi bi-hdd-network"></i>API Keys</a>
            <a class="nav-link <?= str_contains($path, '/admin/logs/api') ? 'active' : '' ?>" href="<?= $base ?>/admin/logs/api"><i class="bi bi-list-columns"></i>API Logs</a>
            <a class="nav-link <?= str_contains($path, '/admin/logs/audit') ? 'active' : '' ?>" href="<?= $base ?>/admin/logs/audit"><i class="bi bi-journal-text"></i>Audit Logs</a>
            <div class="elms-nav-label">Help</div>
            <a class="nav-link <?= str_contains($path, '/admin/docs') ? 'active' : '' ?>" href="<?= $base ?>/admin/docs"><i class="bi bi-book"></i>Documentation</a>
        </nav>
        <div class="elms-side-foot">
            <i class="bi bi-shield-check me-1" style="color:#6ee7b7"></i> Encrypted &amp; signed
            <div class="mt-1 small">v1.0 · <?= date('Y') ?></div>
        </div>
    </aside>

    <div class="elms-backdrop" id="elmsBackdrop"></div>

    <!-- Content -->
    <div class="elms-content">
        <header class="elms-topbar">
            <button class="elms-menu-btn" id="elmsSidebarToggle" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
            <div class="elms-topbar-title d-none d-sm-block"><?= View::e($title ?? 'Console') ?></div>
            <div class="elms-topbar-spacer"></div>
            <span class="elms-pill d-none d-md-inline-flex"><i class="bi bi-shield-check"></i> Live</span>
            <div class="dropdown">
                <div class="elms-user" id="elmsUserBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="elms-user-name d-none d-sm-inline"><?= View::e($admin['name'] ?? 'Admin') ?></span>
                    <span class="elms-avatar"><?= View::e($initials ?: 'A') ?></span>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="px-3 py-2 text-muted small"><?= View::e($admin['username'] ?? '') ?></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= $base ?>/admin/logout"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                </ul>
            </div>
        </header>

        <main class="elms-main container-fluid p-4">
            <?php $flash = $flash ?? []; ?>
            <?php if (!empty($flash['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= View::e($flash['success']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= View::e($flash['error']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.ELMS_BASE = <?= json_encode($base) ?>;
</script>
<script src="<?= $base ?>/assets/js/admin.js"></script>
</body>
</html>
