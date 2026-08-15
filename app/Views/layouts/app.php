<?php
/** @var string $title */
/** @var string $content */
use App\Core\View;
use App\Core\Config;

$admin = $_SESSION['admin'] ?? null;
$path  = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'ELMS') ?> &middot; <?= View::e(Config::get('app.name', 'ELMS')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <aside class="elms-sidebar text-white d-flex flex-column">
        <div class="px-3 py-4 border-bottom border-secondary">
            <span class="fs-5 fw-semibold"><i class="bi bi-shield-lock me-2"></i>ELMS</span>
            <div class="small text-secondary">License Manager</div>
        </div>
        <nav class="nav flex-column p-2 flex-grow-1">
            <a class="nav-link <?= (rtrim($path, '/') === $base . '/admin') ? 'active' : '' ?>" href="<?= $base ?>/admin"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
            <a class="nav-link <?= str_contains($path, '/admin/licenses') ? 'active' : '' ?>" href="<?= $base ?>/admin/licenses"><i class="bi bi-key me-2"></i>Licenses</a>
            <a class="nav-link <?= str_contains($path, '/admin/products') ? 'active' : '' ?>" href="<?= $base ?>/admin/products"><i class="bi bi-box-seam me-2"></i>Products</a>
            <a class="nav-link <?= str_contains($path, '/admin/apikeys') ? 'active' : '' ?>" href="<?= $base ?>/admin/apikeys"><i class="bi bi-hdd-network me-2"></i>API Keys</a>
            <a class="nav-link <?= str_contains($path, '/admin/logs/api') ? 'active' : '' ?>" href="<?= $base ?>/admin/logs/api"><i class="bi bi-list-columns me-2"></i>API Logs</a>
            <a class="nav-link <?= str_contains($path, '/admin/logs/audit') ? 'active' : '' ?>" href="<?= $base ?>/admin/logs/audit"><i class="bi bi-journal-text me-2"></i>Audit Logs</a>
        </nav>
        <div class="p-3 border-top border-secondary small">
            <div class="text-secondary">Signed in as</div>
            <div class="fw-semibold"><?= View::e($admin['name'] ?? 'Admin') ?></div>
            <a class="btn btn-sm btn-outline-light mt-2 w-100" href="<?= $base ?>/admin/logout"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </aside>

    <!-- Main -->
    <main class="elms-main flex-grow-1">
        <div class="container-fluid p-4">
            <?php $flash = $flash ?? []; ?>
            <?php if (!empty($flash['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= View::e($flash['success']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= View::e($flash['error']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.ELMS_BASE = <?= json_encode($base) ?>;
</script>
<script src="<?= $base ?>/assets/js/admin.js"></script>
</body>
</html>
