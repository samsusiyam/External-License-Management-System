<?php
use App\Core\View;
$base = View::basePath();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Already Installed &middot; ELMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            max-width: 580px;
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
            text-align: center;
        }
        .brand-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
            color: #fff;
            margin-bottom: 1.25rem;
            box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
        }
        h1 {
            font-family: 'Sora', sans-serif;
            font-size: 1.65rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        p {
            color: #94a3b8;
            font-size: 0.95rem;
            margin-bottom: 1.75rem;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none;
            color: #fff;
            padding: 0.75rem 1.75rem;
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
    </style>
</head>
<body class="elms-install-body">
    <div class="install-card">
        <div class="brand-icon"><i class="bi bi-shield-check"></i></div>
        <h1>ELMS is Already Installed</h1>
        <p>Your External License Management System is already configured and ready to use. If you need to re-install, please remove <code class="text-info">storage/installed.lock</code>.</p>
        <div>
            <a href="<?= $base ?>/admin/login" class="btn btn-primary-custom">
                <i class="bi bi-box-arrow-in-right"></i> Go to Admin Sign In
            </a>
        </div>
    </div>
</body>
</html>
