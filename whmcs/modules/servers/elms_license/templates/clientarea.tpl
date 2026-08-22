<div class="elms-license-wrapper mb-4" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <style>
        .elms-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }
        .elms-header {
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            padding: 18px 24px;
        }
        .elms-title {
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        .elms-version-badge {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            letter-spacing: 0.3px;
        }
        .elms-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            letter-spacing: 0.3px;
        }
        .elms-status-active {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .elms-status-suspended {
            background: #fefce8;
            color: #854d0e;
            border: 1px solid #fef08a;
        }
        .elms-status-expired {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
        }
        .elms-status-terminated {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .elms-status-pending {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .elms-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
        }
        .elms-status-active .elms-dot { background: #10b981; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2); }
        .elms-status-suspended .elms-dot { background: #eab308; }
        .elms-status-expired .elms-dot { background: #ea580c; }
        .elms-status-terminated .elms-dot { background: #ef4444; }
        .elms-status-pending .elms-dot { background: #94a3b8; }

        .elms-key-box {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            border-radius: 10px;
            padding: 18px 22px;
            border: 1px solid #334155;
            position: relative;
        }
        .elms-key-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .elms-key-value {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            color: #38bdf8;
            word-break: break-all;
        }
        .elms-copy-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 7px 16px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .elms-copy-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        .elms-copy-btn.copied {
            background: #10b981 !important;
            border-color: #10b981 !important;
            color: #ffffff !important;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);
        }

        .elms-prop-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 16px;
            height: 100%;
            transition: border-color 0.2s ease;
        }
        .elms-prop-card:hover {
            border-color: #cbd5e1;
        }
        .elms-prop-title {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .elms-prop-val {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }

        .elms-download-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
        }
        .elms-dl-btn {
            background: #0284c7;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s ease;
        }
        .elms-dl-btn:hover {
            background: #0369a1;
            color: #ffffff;
            text-decoration: none;
        }
    </style>

    <div class="elms-card">
        <!-- Modern Card Header -->
        <div class="elms-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h4 class="elms-title">
                    <i class="fas fa-shield-alt text-primary"></i> Software License Details
                </h4>
                {if $latest_version}
                    <span class="elms-version-badge">v{$latest_version}</span>
                {/if}
            </div>
            <div>
                {if $service_status|lower eq 'active'}
                    <span class="elms-status-badge elms-status-active"><span class="elms-dot"></span> Active</span>
                {elseif $service_status|lower eq 'suspended'}
                    <span class="elms-status-badge elms-status-suspended"><span class="elms-dot"></span> Suspended</span>
                {elseif $service_status|lower eq 'expired'}
                    <span class="elms-status-badge elms-status-expired"><span class="elms-dot"></span> Expired</span>
                {elseif $service_status|lower eq 'terminated'}
                    <span class="elms-status-badge elms-status-terminated"><span class="elms-dot"></span> Terminated</span>
                {elseif $service_status|lower eq 'pending'}
                    <span class="elms-status-badge elms-status-pending"><span class="elms-dot"></span> Pending</span>
                {else}
                    <span class="elms-status-badge elms-status-pending">{$service_status}</span>
                {/if}
            </div>
        </div>

        <div class="card-body p-4">
            <!-- Modern Dark License Key Box -->
            <div class="elms-key-box mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="elms-key-label">License Key</div>
                        <div class="elms-key-value" id="elmsLicenseKeyDisplay">
                            {if $license_key}{$license_key}{else}<span style="color:#64748b; font-size:14px; font-weight:normal;">Generating license key...</span>{/if}
                        </div>
                    </div>
                    {if $license_key}
                        <div>
                            <button type="button" class="elms-copy-btn" id="elmsCopyBtn" onclick="copyElmsKeyModern(this)">
                                <i class="far fa-clone"></i> <span>Copy Key</span>
                            </button>
                        </div>
                    {/if}
                </div>
            </div>

            <!-- Modern Properties Grid -->
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="elms-prop-card">
                        <div class="elms-prop-title">Registered Domain</div>
                        <div class="elms-prop-val">
                            {if $domain}
                                <i class="fas fa-globe text-primary me-1"></i> {$domain}
                            {else}
                                <span class="text-muted">Any Domain (Unrestricted)</span>
                            {/if}
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="elms-prop-card">
                        <div class="elms-prop-title">License Status</div>
                        <div class="elms-prop-val">
                            {if $service_status|lower eq 'active'}
                                <span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Active</span>
                            {elseif $service_status|lower eq 'suspended'}
                                <span class="text-warning fw-bold"><i class="fas fa-pause-circle me-1"></i> Suspended</span>
                            {elseif $service_status|lower eq 'expired'}
                                <span style="color: #ea580c !important;" class="fw-bold"><i class="fas fa-clock me-1"></i> Expired</span>
                            {elseif $service_status|lower eq 'terminated'}
                                <span class="text-danger fw-bold"><i class="fas fa-times-circle me-1"></i> Terminated</span>
                            {elseif $service_status|lower eq 'pending'}
                                <span class="text-muted fw-bold"><i class="fas fa-hourglass-half me-1"></i> Pending</span>
                            {else}
                                <span class="text-primary fw-bold">{$service_status}</span>
                            {/if}
                        </div>
                    </div>
                </div>

                {if $ip_address}
                    <div class="col-md-6">
                        <div class="elms-prop-card">
                            <div class="elms-prop-title">Registered IP Address</div>
                            <div class="elms-prop-val">
                                <i class="fas fa-network-wired text-primary me-1"></i> {$ip_address}
                            </div>
                        </div>
                    </div>
                {/if}

                <div class="col-md-6">
                    <div class="elms-prop-card">
                        <div class="elms-prop-title">Product</div>
                        <div class="elms-prop-val">
                            <i class="fas fa-cube text-primary me-1"></i> {$product_name}
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="elms-prop-card">
                        <div class="elms-prop-title">License Expiry / Renewal Date</div>
                        <div class="elms-prop-val">
                            <i class="fas fa-calendar-alt text-primary me-1"></i> {$nextduedate|default:'Lifetime / Perpetual'}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Software Package & Download Action Bar -->
            <div class="mt-4 pt-3 border-top">
                <div class="elms-download-card d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                            <i class="fas fa-box-open text-primary"></i> Software Package & Updates
                            {if $latest_version}
                                <span class="elms-version-badge" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">v{$latest_version}</span>
                            {/if}
                        </div>
                        <div class="text-muted small mt-1">
                            Download the official software package for installation and updates.
                        </div>
                    </div>
                    <div>
                        {if $download_url}
                            <a href="{$download_url}" target="_blank" class="elms-dl-btn">
                                <i class="fas fa-download"></i> Download {if $latest_version}v{$latest_version}{else}Package{/if}
                            </a>
                        {else}
                            <a href="downloads.php" class="elms-dl-btn">
                                <i class="fas fa-download"></i> Download Files
                            </a>
                        {/if}
                    </div>
                </div>

                <!-- Update Notes / Changelog -->
                {if $update_notes}
                    <div class="mt-3 p-3 rounded" style="background: #ffffff; border: 1px solid #e2e8f0;">
                        <div class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1">
                            <i class="fas fa-info-circle text-info"></i> What's New {if $latest_version}(v{$latest_version}){/if}:
                        </div>
                        <div class="text-muted small" style="white-space: pre-line; line-height: 1.6; font-family: inherit;">{$update_notes}</div>
                    </div>
                {/if}
            </div>
        </div>
    </div>
</div>

<script>
function copyElmsKeyModern(btn) {
    var keyEl = document.getElementById('elmsLicenseKeyDisplay');
    if (!keyEl) return;
    var key = keyEl.innerText.trim();
    if (!key || key.indexOf('Generating') !== -1) return;

    var originalHtml = btn.innerHTML;

    function markSuccess() {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fas fa-check"></i> <span>Copied!</span>';
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = originalHtml;
        }, 2000);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(key).then(markSuccess).catch(function() {
            fallbackCopy(key);
            markSuccess();
        });
    } else {
        fallbackCopy(key);
        markSuccess();
    }

    function fallbackCopy(text) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        textarea.style.top = '-9999px';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
            document.execCommand('copy');
        } catch (e) {}
        document.body.removeChild(textarea);
    }
}
</script>
