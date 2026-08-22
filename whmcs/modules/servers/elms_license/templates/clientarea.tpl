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
                    <div class="fw-bold fs-6 mt-1 text-dark">
                        {if $domain}
                            <i class="fas fa-globe text-primary me-1"></i> {$domain}
                        {else}
                            <span class="text-muted">Any Domain (Unrestricted)</span>
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
                        <i class="fas fa-calendar-alt text-primary me-1"></i> {$nextduedate|default:'Lifetime / Perpetual'}
                    </div>
                </div>
            </div>
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
