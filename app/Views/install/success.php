<?php
use App\Core\View;
/** @var array<string,string> $summary */
$base = View::basePath();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation Complete &middot; ELMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base ?>/assets/css/admin.css" rel="stylesheet">
    <style>
        body.elms-install-body {
            background: #090d16 radial-gradient(circle at 50% 0%, rgba(99, 102, 241, 0.15), transparent 50%);
            min-height: 100vh;
            color: #f1f5f9;
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .install-card {
            width: 100%;
            max-width: 700px;
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
        }
        .brand-icon {
            width: 58px;
            height: 58px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
            color: #fff;
            margin-bottom: 1rem;
            box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
        }
        h1 {
            font-family: 'Sora', sans-serif;
            font-size: 1.65rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .cred-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin: 1.5rem 0;
        }
        .cred-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.65rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .cred-row:last-child {
            border-bottom: none;
        }
        .cred-label {
            font-size: 0.85rem;
            color: #94a3b8;
            font-weight: 600;
        }
        .cred-val {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.88rem;
            color: #818cf8;
            background: rgba(99, 102, 241, 0.1);
            padding: 0.2rem 0.6rem;
            border-radius: 0.35rem;
            border: 1px solid rgba(99, 102, 241, 0.2);
            word-break: break-all;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none;
            color: #fff;
            padding: 0.75rem 2rem;
            border-radius: 0.65rem;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: #fff;
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
        }
        .copy-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.2rem 0.4rem;
            font-size: 0.9rem;
        }
        .copy-btn:hover {
            color: #f8fafc;
        }
    </style>
</head>
<body class="elms-install-body">
    <div class="install-card text-center">
        <div class="brand-icon"><i class="bi bi-check-lg"></i></div>
        <h1>Installation Complete!</h1>
        <p class="text-muted">ELMS has been successfully installed and configured. Your database schema and initial administrator credentials are ready.</p>

        <div class="cred-card text-start">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold small text-uppercase text-light"><i class="bi bi-key-fill text-warning me-1"></i> Generated Credentials</span>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">Copy &amp; save securely</span>
            </div>

            <div class="cred-row">
                <span class="cred-label">Admin Username</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="cred-val" id="valUser"><?= View::e($summary['admin_user']) ?></span>
                    <button class="copy-btn" onclick="copyText('valUser', this)"><i class="bi bi-clipboard"></i></button>
                </div>
            </div>

            <div class="cred-row">
                <span class="cred-label">Admin Password</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="cred-val" id="valPass"><?= View::e($summary['admin_pass']) ?></span>
                    <button class="copy-btn" onclick="copyText('valPass', this)"><i class="bi bi-clipboard"></i></button>
                </div>
            </div>

            <?php if (!empty($summary['api_key'])): ?>
            <div class="cred-row">
                <span class="cred-label">Default API Key</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="cred-val" id="valApiKey"><?= View::e($summary['api_key']) ?></span>
                    <button class="copy-btn" onclick="copyText('valApiKey', this)"><i class="bi bi-clipboard"></i></button>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($summary['api_secret'])): ?>
            <div class="cred-row">
                <span class="cred-label">Default API Secret</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="cred-val" id="valApiSecret"><?= View::e($summary['api_secret']) ?></span>
                    <button class="copy-btn" onclick="copyText('valApiSecret', this)"><i class="bi bi-clipboard"></i></button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="alert alert-info py-2 mb-4 text-start small">
            <i class="bi bi-info-circle-fill me-1"></i> <strong>Security Notice:</strong> The installation lock (<code class="text-white">storage/installed.lock</code>) and <code class="text-white">.env</code> have been saved. Your <code>.env</code> file is ignored by Git and will not be pushed to GitHub.
        </div>

        <div>
            <a href="<?= View::e($summary['login_url']) ?>" class="btn btn-primary-custom">
                <i class="bi bi-box-arrow-in-right"></i> Log in to Admin Panel
            </a>
        </div>
    </div>

    <script>
        function copyText(elemId, btn) {
            const text = document.getElementById(elemId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                const icon = btn.querySelector('i');
                icon.className = 'bi bi-check-lg text-success';
                setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 2000);
            });
        }
    </script>
</body>
</html>
