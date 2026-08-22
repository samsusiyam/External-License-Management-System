<div class="card mb-4" style="border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); overflow: hidden;">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2" style="padding: 16px 20px;">
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fs-5 fw-bold text-white">
                <i class="fas fa-shield-alt me-1"></i> Software License Details
            </h4>
            {if $latest_version}
                <span class="badge bg-light text-dark fw-bold" style="font-size: 12px; border-radius: 12px;">v{$latest_version}</span>
            {/if}
        </div>
        <div>
            {if $service_status|lower eq 'active'}
                <span class="badge bg-success" style="font-size: 13px; padding: 6px 14px; border-radius: 20px;"><i class="fas fa-check-circle me-1"></i> Active</span>
            {elseif $service_status|lower eq 'suspended'}
                <span class="badge bg-warning text-dark" style="font-size: 13px; padding: 6px 14px; border-radius: 20px;"><i class="fas fa-pause-circle me-1"></i> Suspended</span>
            {elseif $service_status|lower eq 'expired'}
                <span class="badge" style="background-color: #ea580c !important; color: #ffffff !important; font-size: 13px; padding: 6px 14px; border-radius: 20px;"><i class="fas fa-clock me-1"></i> Expired</span>
            {elseif $service_status|lower eq 'terminated'}
                <span class="badge bg-danger" style="font-size: 13px; padding: 6px 14px; border-radius: 20px;"><i class="fas fa-times-circle me-1"></i> Terminated</span>
            {elseif $service_status|lower eq 'pending'}
                <span class="badge bg-secondary" style="font-size: 13px; padding: 6px 14px; border-radius: 20px;"><i class="fas fa-hourglass-half me-1"></i> Pending</span>
            {else}
                <span class="badge bg-secondary" style="font-size: 13px; padding: 6px 14px; border-radius: 20px;">{$service_status}</span>
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
                    <div class="text-muted small">License Status</div>
                    <div class="fw-bold fs-6 mt-1">
                        {if $service_status|lower eq 'active'}
                            <span class="text-success"><i class="fas fa-check-circle me-1"></i> Active</span>
                        {elseif $service_status|lower eq 'suspended'}
                            <span class="text-warning"><i class="fas fa-pause-circle me-1"></i> Suspended</span>
                        {elseif $service_status|lower eq 'expired'}
                            <span style="color: #ea580c !important;"><i class="fas fa-clock me-1"></i> Expired</span>
                        {elseif $service_status|lower eq 'terminated'}
                            <span class="text-danger"><i class="fas fa-times-circle me-1"></i> Terminated</span>
                        {elseif $service_status|lower eq 'pending'}
                            <span class="text-muted"><i class="fas fa-hourglass-half me-1"></i> Pending Activation</span>
                        {else}
                            <span class="text-primary"><i class="fas fa-info-circle me-1"></i> {$service_status}</span>
                        {/if}
                    </div>
                </div>
            </div>
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

        <!-- Live Version & Download Action Card -->
        <div class="mt-4 pt-3 border-top">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 rounded" style="background: #f1f5f9; border: 1px solid #e2e8f0;">
                <div>
                    <div class="fw-bold text-dark fs-6">
                        <i class="fas fa-box-open text-primary me-1"></i> Software Package & Updates
                        {if $latest_version}
                            <span class="badge bg-primary ms-1">v{$latest_version}</span>
                        {/if}
                    </div>
                    <div class="text-muted small mt-1">
                        Download the official software package for installation and updates.
                    </div>
                </div>
                <div>
                    {if $download_url}
                        <a href="{$download_url}" target="_blank" class="btn btn-primary px-3 fw-semibold">
                            <i class="fas fa-download me-1"></i> Download {if $latest_version}v{$latest_version}{else}Package{/if}
                        </a>
                    {else}
                        <a href="downloads.php" class="btn btn-primary px-3 fw-semibold">
                            <i class="fas fa-download me-1"></i> Download Files
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Update Notes / Changelog (if available from ELMS server) -->
            {if $update_notes}
                <div class="mt-3 p-3 rounded" style="background: #ffffff; border: 1px solid #e2e8f0;">
                    <div class="fw-bold text-dark small mb-2">
                        <i class="fas fa-info-circle text-info me-1"></i> Update Notes / What's New {if $latest_version}(v{$latest_version}){/if}:
                    </div>
                    <div class="text-muted small font-monospace" style="white-space: pre-line; line-height: 1.6;">{$update_notes}</div>
                </div>
            {/if}
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
