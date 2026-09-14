<?php
use App\Core\View;
/** @var array<string,mixed> $requirements */
/** @var bool $allPassed */
/** @var string $autoUrl */
$base = View::basePath();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation Wizard &middot; ELMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base ?>/assets/css/admin.css" rel="stylesheet">
    <style>
        :root {
            --elms-primary: #4f46e5;
            --elms-primary-hover: #4338ca;
            --elms-bg: #0f172a;
            --elms-card-bg: #1e293b;
            --elms-border: #334155;
            --elms-text: #f8fafc;
            --elms-muted: #94a3b8;
        }
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
            max-width: 820px;
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }
        .install-header {
            padding: 2rem 2.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(255, 255, 255, 0.02);
            text-align: center;
        }
        .install-header .brand-icon {
            width: 54px;
            height: 54px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #fff;
            margin-bottom: 1rem;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }
        .install-header h1 {
            font-family: 'Sora', sans-serif;
            font-size: 1.65rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }
        .install-header p {
            color: #94a3b8;
            font-size: 0.95rem;
            margin-bottom: 0;
        }
        .wizard-steps {
            display: flex;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(0, 0, 0, 0.2);
        }
        .wizard-step {
            flex: 1;
            padding: 1rem;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .wizard-step.active {
            color: #818cf8;
            border-bottom-color: #6366f1;
            background: rgba(99, 102, 241, 0.05);
        }
        .wizard-step.completed {
            color: #34d399;
        }
        .install-body {
            padding: 2.25rem 2.5rem;
        }
        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #cbd5e1;
            margin-bottom: 0.4rem;
        }
        .form-control, .form-select {
            background-color: #1e293b;
            border: 1px solid #334155;
            color: #f8fafc;
            border-radius: 0.6rem;
            padding: 0.65rem 0.9rem;
            font-size: 0.92rem;
        }
        .form-control:focus, .form-select:focus {
            background-color: #1e293b;
            border-color: #6366f1;
            color: #f8fafc;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }
        .input-group-text {
            background-color: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }
        .req-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.65rem 0.85rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.88rem;
        }
        .req-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 2rem;
            font-weight: 600;
        }
        .req-badge.pass {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .req-badge.fail {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 0.65rem;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        .btn-primary-custom:hover:not(:disabled) {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
            color: #fff;
        }
        .btn-secondary-custom {
            background: #1e293b;
            border: 1px solid #334155;
            color: #cbd5e1;
            padding: 0.75rem 1.25rem;
            border-radius: 0.65rem;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .btn-secondary-custom:hover {
            background: #334155;
            color: #fff;
        }
        .section-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .step-panel {
            display: none;
        }
        .step-panel.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .code-box {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            color: #a5b4fc;
        }
    </style>
</head>
<body class="elms-install-body">
    <div class="install-card">
        <div class="install-header">
            <div class="brand-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <h1>ELMS Installation Wizard</h1>
            <p>Set up your External License Management System in minutes</p>
        </div>

        <div class="wizard-steps">
            <div class="wizard-step active" id="stepIndicator1">
                <i class="bi bi-cpu"></i> 1. Requirements
            </div>
            <div class="wizard-step" id="stepIndicator2">
                <i class="bi bi-database"></i> 2. Database
            </div>
            <div class="wizard-step" id="stepIndicator3">
                <i class="bi bi-person-gear"></i> 3. Administrator & App
            </div>
        </div>

        <div class="install-body">
            <form id="installForm" method="post" action="<?= $base ?>/install/process">

                <!-- STEP 1: Requirements Check -->
                <div class="step-panel active" id="stepPanel1">
                    <div class="section-title">
                        <i class="bi bi-check2-circle text-primary"></i> Server Compatibility &amp; Permissions
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted small text-uppercase fw-bold mb-2">PHP &amp; Core Extensions</h6>
                            <?php foreach ($requirements['requirements'] as $req): ?>
                                <div class="req-item">
                                    <span><?= View::e($req['name']) ?></span>
                                    <span class="req-badge <?= $req['passed'] ? 'pass' : 'fail' ?>">
                                        <?= $req['passed'] ? '<i class="bi bi-check-lg me-1"></i>' . View::e($req['current']) : '<i class="bi bi-x-lg me-1"></i>' . View::e($req['current']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-muted small text-uppercase fw-bold mb-2">Directory Write Permissions</h6>
                            <?php foreach ($requirements['permissions'] as $perm): ?>
                                <div class="req-item">
                                    <span class="font-monospace small"><?= View::e($perm['path']) ?></span>
                                    <span class="req-badge <?= $perm['passed'] ? 'pass' : 'fail' ?>">
                                        <?= $perm['passed'] ? '<i class="bi bi-check-lg me-1"></i>' . View::e($perm['current']) : '<i class="bi bi-x-lg me-1"></i>' . View::e($perm['current']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!$allPassed): ?>
                        <div class="alert alert-danger py-2 mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Some requirements are missing or directories are not writable. Please fix them before continuing.
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-primary-custom" id="btnNextToStep2" <?= !$allPassed ? 'disabled' : '' ?>>
                            Continue to Database Setup <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Database Configuration -->
                <div class="step-panel" id="stepPanel2">
                    <div class="section-title">
                        <i class="bi bi-database-gear text-primary"></i> Database Configuration
                    </div>
                    <p class="text-muted small mb-4">Enter your MySQL / MariaDB connection credentials. If the database does not exist, the installer will attempt to create it automatically.</p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Database Host</label>
                            <input type="text" name="db_host" id="dbHost" class="form-control" value="localhost" placeholder="localhost or 127.0.0.1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Port</label>
                            <input type="number" name="db_port" id="dbPort" class="form-control" value="3306" placeholder="3306" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Database Name</label>
                            <input type="text" name="db_name" id="dbName" class="form-control" placeholder="e.g. elms_license" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Database Username</label>
                            <input type="text" name="db_user" id="dbUser" class="form-control" value="root" placeholder="e.g. root or cpanel_user" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Database Password</label>
                            <div class="input-group">
                                <input type="password" name="db_pass" id="dbPass" class="form-control" placeholder="Database password">
                                <button class="btn btn-outline-secondary" type="button" id="toggleDbPass"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <div id="dbTestAlert" class="alert d-none py-2 mb-3"></div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="button" class="btn btn-secondary-custom" id="btnBackToStep1">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary-custom" id="btnTestDb">
                                <i class="bi bi-arrow-repeat me-1"></i> Test Connection
                            </button>
                            <button type="button" class="btn btn-primary-custom" id="btnNextToStep3">
                                Next: Administrator Setup <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Admin & App Settings -->
                <div class="step-panel" id="stepPanel3">
                    <div class="section-title">
                        <i class="bi bi-shield-lock text-primary"></i> Application &amp; Administrator Account
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Application Name</label>
                            <input type="text" name="app_name" class="form-control" value="External License Manager" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Application URL (Base URL)</label>
                            <input type="url" name="app_url" class="form-control" value="<?= View::e($autoUrl) ?>" required>
                        </div>
                    </div>

                    <h6 class="text-muted small text-uppercase fw-bold mb-3">Primary Admin Account</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Admin Full Name</label>
                            <input type="text" name="admin_name" class="form-control" value="Administrator" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admin Username</label>
                            <input type="text" name="admin_user" class="form-control" value="admin" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admin Email</label>
                            <input type="email" name="admin_email" class="form-control" value="admin@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admin Password</label>
                            <div class="input-group">
                                <input type="password" name="admin_pass" id="adminPass" class="form-control" placeholder="Min. 8 characters" minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleAdminPass"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <div id="installAlert" class="alert d-none py-2 mb-3"></div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="button" class="btn btn-secondary-custom" id="btnBackToStep2">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </button>
                        <button type="submit" class="btn btn-primary-custom" id="btnInstallSubmit">
                            <i class="bi bi-box-arrow-in-down me-1"></i> Install ELMS Now
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const basePath = <?= json_encode($base) ?>;

        // Step Navigation
        function showStep(stepNum) {
            document.querySelectorAll('.step-panel').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));

            document.getElementById('stepPanel' + stepNum).classList.add('active');
            const ind = document.getElementById('stepIndicator' + stepNum);
            if (ind) ind.classList.add('active');
        }

        document.getElementById('btnNextToStep2').addEventListener('click', () => showStep(2));
        document.getElementById('btnBackToStep1').addEventListener('click', () => showStep(1));
        document.getElementById('btnNextToStep3').addEventListener('click', () => {
            const dbName = document.getElementById('dbName').value.trim();
            const dbUser = document.getElementById('dbUser').value.trim();
            if (!dbName || !dbUser) {
                const alertEl = document.getElementById('dbTestAlert');
                alertEl.className = 'alert alert-warning py-2 mb-3';
                alertEl.textContent = 'Please provide Database Name and Database User.';
                alertEl.classList.remove('d-none');
                return;
            }
            showStep(3);
        });
        document.getElementById('btnBackToStep2').addEventListener('click', () => showStep(2));

        // Toggle Passwords
        document.getElementById('toggleDbPass').addEventListener('click', function() {
            const el = document.getElementById('dbPass');
            el.type = el.type === 'password' ? 'text' : 'password';
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });
        document.getElementById('toggleAdminPass').addEventListener('click', function() {
            const el = document.getElementById('adminPass');
            el.type = el.type === 'password' ? 'text' : 'password';
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });

        // Test Database Connection AJAX
        document.getElementById('btnTestDb').addEventListener('click', async function() {
            const btn = this;
            const alertEl = document.getElementById('dbTestAlert');
            const host = document.getElementById('dbHost').value.trim();
            const port = document.getElementById('dbPort').value.trim();
            const name = document.getElementById('dbName').value.trim();
            const user = document.getElementById('dbUser').value.trim();
            const pass = document.getElementById('dbPass').value;

            if (!host || !name || !user) {
                alertEl.className = 'alert alert-warning py-2 mb-3';
                alertEl.textContent = 'Please fill in Host, Database Name, and User.';
                alertEl.classList.remove('d-none');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testing...';
            alertEl.classList.add('d-none');

            try {
                const res = await fetch(basePath + '/install/test-db', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ db_host: host, db_port: port, db_name: name, db_user: user, db_pass: pass })
                });
                const data = await res.json();
                if (data.status) {
                    alertEl.className = 'alert alert-success py-2 mb-3';
                    alertEl.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>' + (data.data?.message || data.message);
                } else {
                    alertEl.className = 'alert alert-danger py-2 mb-3';
                    alertEl.innerHTML = '<i class="bi bi-exclamation-octagon-fill me-2"></i>' + (data.message || 'Connection failed.');
                }
                alertEl.classList.remove('d-none');
            } catch (err) {
                alertEl.className = 'alert alert-danger py-2 mb-3';
                alertEl.innerHTML = '<i class="bi bi-exclamation-octagon-fill me-2"></i>Network request failed: ' + err.message;
                alertEl.classList.remove('d-none');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Test Connection';
            }
        });

        // Submit Installer
        document.getElementById('installForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnInstallSubmit');
            const alertEl = document.getElementById('installAlert');
            const formData = new FormData(this);
            const bodyObj = Object.fromEntries(formData.entries());

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Installing ELMS...';
            alertEl.classList.add('d-none');

            try {
                const res = await fetch(basePath + '/install/process', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(bodyObj)
                });
                const data = await res.json();
                if (data.status) {
                    alertEl.className = 'alert alert-success py-2 mb-3';
                    alertEl.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Installation successful! Redirecting...';
                    alertEl.classList.remove('d-none');
                    setTimeout(() => {
                        window.location.href = data.data?.redirect || (basePath + '/install/success');
                    }, 1000);
                } else {
                    alertEl.className = 'alert alert-danger py-2 mb-3';
                    alertEl.innerHTML = '<i class="bi bi-exclamation-octagon-fill me-2"></i>' + (data.message || 'Installation failed.');
                    alertEl.classList.remove('d-none');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i> Install ELMS Now';
                }
            } catch (err) {
                alertEl.className = 'alert alert-danger py-2 mb-3';
                alertEl.innerHTML = '<i class="bi bi-exclamation-octagon-fill me-2"></i>' + err.message;
                alertEl.classList.remove('d-none');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i> Install ELMS Now';
            }
        });
    </script>
</body>
</html>
