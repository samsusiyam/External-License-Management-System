{if $elms_success_msg}
    <div class="alert alert-success" style="border-radius: 8px; font-weight: 500; margin-bottom: 20px;">
        <i class="fas fa-check-circle me-1"></i> {$elms_success_msg}
    </div>
{/if}
{if $elms_error_msg}
    <div class="alert alert-danger" style="border-radius: 8px; font-weight: 500; margin-bottom: 20px;">
        <i class="fas fa-times-circle me-1"></i> {$elms_error_msg}
    </div>
{/if}

<div class="card mb-4" style="border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); overflow: hidden;">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" style="padding: 16px 20px;">
        <h4 class="mb-0 fs-5 fw-bold text-white">
            <i class="fas fa-shield-alt me-2"></i> Software License Details
        </h4>
        <div>
            {if $service_status eq 'Active'}
                <span class="badge bg-success" style="font-size: 13px; padding: 6px 12px; border-radius: 20px;">Active</span>
            {elseif $service_status eq 'Suspended'}
                <span class="badge bg-warning text-dark" style="font-size: 13px; padding: 6px 12px; border-radius: 20px;">Suspended</span>
            {elseif $service_status eq 'Terminated'}
                <span class="badge bg-danger" style="font-size: 13px; padding: 6px 12px; border-radius: 20px;">Terminated</span>
            {else}
                <span class="badge bg-secondary" style="font-size: 13px; padding: 6px 12px; border-radius: 20px;">{$service_status}</span>
            {/if}
        </div>
    </div>
    <div class="card-body p-4">
        <!-- License Key Display Box -->
        <div class="p-3 mb-4 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
            <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing: 0.5px;">Your License Key</div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="font-monospace fs-5 fw-bold text-dark" id="elmsLicenseKeyDisplay">
                    {if $license_key}{$license_key}{else}<span class="text-muted">Generating upon order activation...</span>{/if}
                </div>
                {if $license_key}
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyElmsKey()">
                            <i class="fas fa-copy me-1"></i> Copy Key
                        </button>
                    </div>
                {/if}
            </div>
        </div>

        <!-- License Properties Grid -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Registered Domain</div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-1">
                        <div class="fw-bold fs-6 text-dark">
                            {if $domain}
                                <i class="fas fa-globe text-primary me-1"></i> {$domain}
                            {else}
                                <span class="text-muted">Any Domain (Unrestricted)</span>
                            {/if}
                        </div>
                        {if $service_status eq 'Active' && $license_key}
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#elmsChangeDomainModal" style="font-size: 12px; padding: 3px 8px;">
                                <i class="fas fa-edit me-1"></i> Change Domain
                            </button>
                        {/if}
                    </div>
                </div>
            </div>
            {if $ip_address}
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Registered IP Address</div>
                        <div class="fw-bold fs-6 mt-1 text-dark">
                            <i class="fas fa-network-wired text-primary me-1"></i> {$ip_address}
                        </div>
                    </div>
                </div>
            {/if}
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Product</div>
                    <div class="fw-bold fs-6 mt-1 text-dark">
                        <i class="fas fa-cube text-primary me-1"></i> {$product_name}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">License Expiry / Renewal Date</div>
                    <div class="fw-bold fs-6 mt-1 text-dark">
                        <i class="fas fa-calendar-alt text-primary me-1"></i> {$nextduedate|default:'Lifetime'}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Domain Modal -->
<div class="modal fade" id="elmsChangeDomainModal" tabindex="-1" aria-labelledby="elmsChangeDomainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="elmsChangeDomainModalLabel"><i class="fas fa-globe me-2 text-primary"></i> Update Registered Domain</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Enter your new domain name (without http:// or www). The license server domain binding will be updated automatically.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">New Domain Name</label>
                        <input type="text" name="elms_new_domain" class="form-control font-monospace" placeholder="example.com" value="{$domain}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="elms_submit_change_domain" value="1" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save & Reissue Domain
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyElmsKey() {
    var key = document.getElementById('elmsLicenseKeyDisplay').innerText.trim();
    if (!key) return;
    navigator.clipboard.writeText(key).then(function() {
        alert('License Key copied to clipboard: ' + key);
    }).catch(function(err) {
        var textarea = document.createElement('textarea');
        textarea.value = key;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('License Key copied to clipboard: ' + key);
    });
}
</script>
