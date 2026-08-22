<?php
/** @var array<int,array<string,mixed>> $apiKeys */
/** @var array<int,array<string,mixed>> $products */
/** @var array<int,array<string,mixed>> $licenses */
/** @var string $appUrl */
use App\Core\View;
?>

<div class="row g-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h2 class="h4 mb-1"><i class="bi bi-play-circle text-primary me-2"></i>API Tester &amp; License Simulator</h2>
                <p class="text-muted mb-0">Test license creation, verification, and lifecycle API endpoints directly from your browser.</p>
            </div>
            <a href="<?= $base ?>/admin/docs" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-book me-1"></i> API Documentation
            </a>
        </div>
    </div>

    <!-- Request Builder Card -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold"><i class="bi bi-sliders me-2"></i>Request Configuration</span>
                <span class="badge bg-primary-subtle text-primary">HMAC-SHA256 Signed</span>
            </div>
            <div class="card-body">
                <form id="testerForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Action / Endpoint</label>
                        <select class="form-select" id="testAction" name="endpoint">
                            <option value="/api/license/verify" selected>Verify License (POST /api/license/verify)</option>
                            <option value="/api/license/create">Create License (POST /api/license/create)</option>
                            <option value="/api/license/activate">Activate License (POST /api/license/activate)</option>
                            <option value="/api/license/deactivate">Deactivate License (POST /api/license/deactivate)</option>
                            <option value="/api/license/renew">Renew License (POST /api/license/renew)</option>
                            <option value="/api/license/reset">Reset Bindings (POST /api/license/reset)</option>
                            <option value="/api/license/suspend">Suspend License (POST /api/license/suspend)</option>
                            <option value="/api/license/unsuspend">Unsuspend License (POST /api/license/unsuspend)</option>
                            <option value="/api/license/terminate">Terminate License (POST /api/license/terminate)</option>
                            <option value="/api/updates/check">Check Updates (POST /api/updates/check)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">API Key for Signing</label>
                        <select class="form-select" id="apiKeySelect" name="api_key_id">
                            <?php if (empty($apiKeys)): ?>
                                <option value="0">No active API key (will use default fallback)</option>
                            <?php else: ?>
                                <?php foreach ($apiKeys as $k): ?>
                                    <option value="<?= (int) $k['id'] ?>"><?= View::e($k['name']) ?> (<?= View::e(substr($k['api_key'], 0, 16)) ?>...)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Quick Pick from Existing License -->
                    <div class="mb-3" id="quickPickGroup">
                        <label class="form-label fw-semibold">Quick-Fill from Existing License</label>
                        <select class="form-select" id="quickLicenseSelect">
                            <option value="">-- Choose a license to auto-fill fields --</option>
                            <?php foreach ($licenses as $lic): ?>
                                <option value="<?= View::e($lic['license_key']) ?>" 
                                        data-domain="<?= View::e($lic['domain'] ?? '') ?>"
                                        data-product-id="<?= (int) $lic['product_id'] ?>"
                                        data-customer="<?= View::e($lic['customer_name'] ?? '') ?>"
                                        data-email="<?= View::e($lic['customer_email'] ?? '') ?>">
                                    <?= View::e($lic['license_key']) ?> [<?= View::e($lic['status']) ?>] - <?= View::e($lic['customer_name'] ?? 'Client') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr class="my-3 text-muted">

                    <!-- Dynamic Form Fields -->
                    <div id="dynamicFields">
                        <div class="mb-3 field-row" data-for="verify,activate,deactivate,renew,reset,suspend,unsuspend,terminate">
                            <label class="form-label fw-semibold">License Key</label>
                            <input type="text" class="form-control font-monospace" id="f_license_key" placeholder="e.g. XXXX-XXXX-XXXX-XXXX">
                        </div>

                        <div class="mb-3 field-row" data-for="verify,activate,deactivate,create">
                            <label class="form-label fw-semibold">Domain Name</label>
                            <input type="text" class="form-control" id="f_domain" placeholder="e.g. clientdomain.com">
                        </div>

                        <div class="mb-3 field-row" data-for="verify,activate,create,updates">
                            <label class="form-label fw-semibold">Product</label>
                            <select class="form-select" id="f_product">
                                <option value="">-- Auto-detect / Any --</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= View::e($p['product_key']) ?>" data-id="<?= (int) $p['id'] ?>">
                                        <?= View::e($p['product_name']) ?> (<?= View::e($p['product_key']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3 field-row d-none" data-for="create">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Customer Name</label>
                                    <input type="text" class="form-control" id="f_customer_name" placeholder="John Doe">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Customer Email</label>
                                    <input type="email" class="form-control" id="f_customer_email" placeholder="john@example.com">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 field-row d-none" data-for="create,renew">
                            <label class="form-label fw-semibold">Expiry Date (YYYY-MM-DD or Days)</label>
                            <input type="text" class="form-control" id="f_expiry_date" placeholder="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                        </div>

                        <div class="mb-3 field-row d-none" data-for="create">
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="form-label fw-semibold">Activation Limit</label>
                                    <input type="number" class="form-control" id="f_activation_limit" value="1" min="1">
                                </div>
                                <div class="col-4 pt-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="f_domain_lock" checked>
                                        <label class="form-check-label" for="f_domain_lock">Domain Lock</label>
                                    </div>
                                </div>
                                <div class="col-4 pt-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="f_ip_lock">
                                        <label class="form-check-label" for="f_ip_lock">IP Lock</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 field-row d-none" data-for="updates">
                            <label class="form-label fw-semibold">Current Installed Version</label>
                            <input type="text" class="form-control" id="f_current_version" placeholder="1.0.0">
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="button" class="btn btn-primary btn-lg" id="btnSendTest">
                            <i class="bi bi-send me-1"></i> Send Test Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Live Response Card -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold"><i class="bi bi-terminal me-2"></i>Live Server Response</span>
                <div id="responseBadges" class="d-flex gap-2 align-items-center">
                    <span class="badge bg-secondary" id="badgeStatus">Ready</span>
                </div>
            </div>
            <div class="card-body d-flex flex-column p-3">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-muted">Request Payload Sent</span>
                        <button class="btn btn-link btn-sm p-0 text-decoration-none" onclick="copySnippet('reqPreview')">Copy</button>
                    </div>
                    <pre class="bg-light p-2 rounded small text-dark font-monospace mb-0" id="reqPreview" style="max-height: 140px; overflow: auto;">{}</pre>
                </div>

                <div id="cfWarning" class="alert alert-warning py-2 px-3 small d-none mb-2"></div>

                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-muted">Response Body (JSON)</span>
                        <button class="btn btn-link btn-sm p-0 text-decoration-none" onclick="copySnippet('respBody')">Copy</button>
                    </div>
                    <pre class="bg-dark text-light p-3 rounded font-monospace mb-0" id="respBody" style="min-height: 250px; max-height: 380px; overflow: auto;">// Response output will appear here after clicking "Send Test Request"...</pre>
                </div>

                <div class="mt-3 pt-2 border-top d-flex justify-content-between text-muted small" id="metaDetails">
                    <span id="metaUrl">Endpoint: /api/license/verify</span>
                    <span id="metaLatency">Latency: -- ms</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testAction = document.getElementById('testAction');
    const quickLicenseSelect = document.getElementById('quickLicenseSelect');
    const dynamicFields = document.querySelectorAll('.field-row');
    const btnSendTest = document.getElementById('btnSendTest');

    function updateVisibleFields() {
        const action = testAction.value;
        let actType = 'verify';
        if (action.includes('create')) actType = 'create';
        else if (action.includes('activate')) actType = 'activate';
        else if (action.includes('deactivate')) actType = 'deactivate';
        else if (action.includes('renew')) actType = 'renew';
        else if (action.includes('reset')) actType = 'reset';
        else if (action.includes('suspend')) actType = 'suspend';
        else if (action.includes('unsuspend')) actType = 'unsuspend';
        else if (action.includes('terminate')) actType = 'terminate';
        else if (action.includes('updates')) actType = 'updates';

        dynamicFields.forEach(el => {
            const forTypes = el.getAttribute('data-for').split(',');
            if (forTypes.includes(actType)) {
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        });

        updatePayloadPreview();
    }

    testAction.addEventListener('change', updateVisibleFields);

    quickLicenseSelect.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (this.value) {
            document.getElementById('f_license_key').value = this.value;
            if (opt.dataset.domain) document.getElementById('f_domain').value = opt.dataset.domain;
            if (opt.dataset.customer) document.getElementById('f_customer_name').value = opt.dataset.customer;
            if (opt.dataset.email) document.getElementById('f_customer_email').value = opt.dataset.email;
        }
        updatePayloadPreview();
    });

    function buildPayload() {
        const action = testAction.value;
        const p = {};

        const licKey = document.getElementById('f_license_key').value.trim();
        const domain = document.getElementById('f_domain').value.trim();
        const prod = document.getElementById('f_product').value.trim();

        if (action.includes('create')) {
            p.product = prod;
            p.customer_name = document.getElementById('f_customer_name').value.trim() || 'John Doe';
            p.customer_email = document.getElementById('f_customer_email').value.trim() || 'john@example.com';
            p.domain = domain;
            p.activation_limit = parseInt(document.getElementById('f_activation_limit').value) || 1;
            p.domain_lock = document.getElementById('f_domain_lock').checked ? 1 : 0;
            p.ip_lock = document.getElementById('f_ip_lock').checked ? 1 : 0;
            p.expiry_date = document.getElementById('f_expiry_date').value.trim() || null;
        } else if (action.includes('updates')) {
            p.product = prod;
            p.current_version = document.getElementById('f_current_version').value.trim() || '1.0.0';
            if (licKey) p.license_key = licKey;
        } else {
            p.license_key = licKey;
            if (domain) p.domain = domain;
            if (prod) p.product = prod;
            if (action.includes('renew')) {
                p.expiry_date = document.getElementById('f_expiry_date').value.trim() || null;
            }
        }
        return p;
    }

    function updatePayloadPreview() {
        const payload = buildPayload();
        document.getElementById('reqPreview').textContent = JSON.stringify(payload, null, 2);
    }

    document.querySelectorAll('#testerForm input, #testerForm select').forEach(el => {
        el.addEventListener('input', updatePayloadPreview);
        el.addEventListener('change', updatePayloadPreview);
    });

    updateVisibleFields();

    btnSendTest.addEventListener('click', function() {
        const payload = buildPayload();
        const endpoint = testAction.value;
        const apiKeyId = document.getElementById('apiKeySelect').value;

        btnSendTest.disabled = true;
        btnSendTest.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';

        document.getElementById('badgeStatus').className = 'badge bg-warning';
        document.getElementById('badgeStatus').textContent = 'Connecting...';
        document.getElementById('respBody').textContent = 'Sending request and computing HMAC-SHA256 signature...';

        fetch(window.ELMS_BASE + '/admin/tester/run', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                endpoint: endpoint,
                api_key_id: apiKeyId,
                payload: payload
            })
        })
        .then(r => r.json())
        .then(data => {
            btnSendTest.disabled = false;
            btnSendTest.innerHTML = '<i class="bi bi-send me-1"></i> Send Test Request';

            const respBadges = document.getElementById('responseBadges');
            let badgeClass = data.success ? 'bg-success' : 'bg-danger';
            respBadges.innerHTML = `
                <span class="badge ${badgeClass}">${data.http_code} ${data.success ? 'Success' : 'Error'}</span>
                <span class="badge bg-dark">${data.duration_ms} ms</span>
                ${data.server_sig_valid === true ? '<span class="badge bg-success-subtle text-success"><i class="bi bi-shield-check"></i> Signed</span>' : ''}
            `;

            const cfWarn = document.getElementById('cfWarning');
            if (data.cloudflare_blocked && data.cloudflare_notice) {
                cfWarn.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + data.cloudflare_notice;
                cfWarn.classList.remove('d-none');
            } else {
                cfWarn.classList.add('d-none');
            }

            document.getElementById('respBody').textContent = JSON.stringify(data.response_body, null, 2);
            document.getElementById('metaUrl').textContent = 'URL: ' + data.target_url;
            document.getElementById('metaLatency').textContent = 'Latency: ' + data.duration_ms + ' ms';
        })
        .catch(err => {
            btnSendTest.disabled = false;
            btnSendTest.innerHTML = '<i class="bi bi-send me-1"></i> Send Test Request';
            document.getElementById('badgeStatus').className = 'badge bg-danger';
            document.getElementById('badgeStatus').textContent = 'Fetch Error';
            document.getElementById('respBody').textContent = 'Network or server error: ' + err.message;
        });
    });
});

function copySnippet(elementId) {
    const text = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}
</script>
