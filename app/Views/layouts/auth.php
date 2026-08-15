<?php
/** @var string $content */
use App\Core\View;
use App\Core\Config;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'Sign in') ?> &middot; <?= View::e(Config::get('app.name', 'ELMS')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body class="elms-login-body">
    <aside class="elms-login-aside">
        <div class="elms-brand-logo"><i class="bi bi-shield-lock"></i></div>
        <h2>License control,<br>locked down.</h2>
        <p>A centralized console to issue, verify and revoke software licenses across all your products.</p>
        <ul class="elms-login-feats">
            <li><i class="bi bi-shield-check"></i> HMAC-signed API requests</li>
            <li><i class="bi bi-fingerprint"></i> Domain &amp; IP binding</li>
            <li><i class="bi bi-clock-history"></i> Full audit trail</li>
        </ul>
    </aside>
    <main class="elms-login-main">
        <?= $content ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
