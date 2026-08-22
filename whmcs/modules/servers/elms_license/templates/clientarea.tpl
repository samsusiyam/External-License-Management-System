<div class="elms-license-wrapper mb-4" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <style>
        /* Light Theme (Default) */
        .elms-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            color: #0f172a;
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
            color: #334155;
            border: 1px solid #cbd5e1;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 16px;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
        }
        .elms-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 14px;
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
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .elms-status-active .elms-dot { background: #10b981; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25); }
        .elms-status-suspended .elms-dot { background: #eab308; }
        .elms-status-expired .elms-dot { background: #ea580c; }
        .elms-status-terminated .elms-dot { background: #ef4444; }
        .elms-status-pending .elms-dot { background: #94a3b8; }

        .elms-key-box {
            background: #0f172a;
            color: #ffffff;
            border-radius: 10px;
            padding: 18px 22px;
            border: 1px solid #334155;
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
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 7px 16px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none !important;
        }
        .elms-copy-btn:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff !important;
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
            color: #ffffff !important;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 20px;
            border-radius: 6px;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            border: 1px solid #0284c7;
        }
        .elms-dl-btn:hover {
            background: #0369a1;
            border-color: #0369a1;
            color: #ffffff !important;
            text-decoration: none !important;
        }
        .elms-notes-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #475569;
        }

        /* -------------------------------------------------------------
           Dark Mode Support (Automatic via Media Query & Theme Selectors)
        ------------------------------------------------------------- */
        @media (prefers-color-scheme: dark) {
            .elms-card {
                background: #1e293b;
                border-color: #334155;
                color: #f8fafc;
            }
            .elms-header {
                background: #1e293b;
                border-color: #334155;
            }
            .elms-title {
                color: #f8fafc;
            }
            .elms-version-badge {
                background: #334155;
                color: #93c5fd;
                border-color: #475569;
            }
            .elms-status-active {
                background: rgba(16, 185, 129, 0.15);
                color: #34d399;
                border-color: rgba(16, 185, 129, 0.3);
            }
            .elms-status-suspended {
                background: rgba(234, 179, 8, 0.15);
                color: #fde047;
                border-color: rgba(234, 179, 8, 0.3);
            }
            .elms-status-expired {
                background: rgba(234, 88, 12, 0.15);
                color: #fb923c;
                border-color: rgba(234, 88, 12, 0.3);
            }
            .elms-status-terminated {
                background: rgba(239, 68, 68, 0.15);
                color: #f87171;
                border-color: rgba(239, 68, 68, 0.3);
            }
            .elms-status-pending {
                background: rgba(148, 163, 184, 0.15);
                color: #cbd5e1;
                border-color: rgba(148, 163, 184, 0.3);
            }
            .elms-prop-card {
                background: #0f172a;
                border-color: #334155;
            }
            .elms-prop-card:hover {
                border-color: #475569;
            }
            .elms-prop-title {
                color: #94a3b8;
            }
            .elms-prop-val {
                color: #f8fafc;
            }
            .elms-download-card {
                background: #0f172a;
                border-color: #334155;
            }
            .elms-notes-box {
                background: #0f172a !important;
                border-color: #334155 !important;
                color: #cbd5e1 !important;
            }
        }

        /* WHMCS Dark Theme Classes (Twenty-One dark, Lagom dark, etc.) */
        body.dark-theme .elms-card,
        body.dark-mode .elms-card,
        body.theme-dark .elms-card,
        body.dark .elms-card,
        [data-theme="dark"] .elms-card,
        [data-bs-theme="dark"] .elms-card,
        .theme-dark .elms-card {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }
        body.dark-theme .elms-header,
        body.dark-mode .elms-header,
        body.theme-dark .elms-header,
        body.dark .elms-header,
        [data-theme="dark"] .elms-header,
        [data-bs-theme="dark"] .elms-header,
        .theme-dark .elms-header {
            background: #1e293b !important;
            border-color: #334155 !important;
        }
        body.dark-theme .elms-title,
        body.dark-mode .elms-title,
        body.theme-dark .elms-title,
        body.dark .elms-title,
        [data-theme="dark"] .elms-title,
        [data-bs-theme="dark"] .elms-title,
        .theme-dark .elms-title {
            color: #f8fafc !important;
        }
        body.dark-theme .elms-version-badge,
        body.dark-mode .elms-version-badge,
        body.theme-dark .elms-version-badge,
        body.dark .elms-version-badge,
        [data-theme="dark"] .elms-version-badge,
        [data-bs-theme="dark"] .elms-version-badge,
        .theme-dark .elms-version-badge {
            background: #334155 !important;
            color: #93c5fd !important;
            border-color: #475569 !important;
        }
        body.dark-theme .elms-prop-card,
        body.dark-mode .elms-prop-card,
        body.theme-dark .elms-prop-card,
        body.dark .elms-prop-card,
        [data-theme="dark"] .elms-prop-card,
        [data-bs-theme="dark"] .elms-prop-card,
        .theme-dark .elms-prop-card {
            background: #0f172a !important;
            border-color: #334155 !important;
        }
        body.dark-theme .elms-prop-title,
        body.dark-mode .elms-prop-title,
        body.theme-dark .elms-prop-title,
        body.dark .elms-prop-title,
        [data-theme="dark"] .elms-prop-title,
        [data-bs-theme="dark"] .elms-prop-title,
        .theme-dark .elms-prop-title {
            color: #94a3b8 !important;
        }
        body.dark-theme .elms-prop-val,
        body.dark-mode .elms-prop-val,
        body.theme-dark .elms-prop-val,
        body.dark .elms-prop-val,
        [data-theme="dark"] .elms-prop-val,
        [data-bs-theme="dark"] .elms-prop-val,
        .theme-dark .elms-prop-val {
            color: #f8fafc !important;
        }
        body.dark-theme .elms-download-card,
        body.dark-mode .elms-download-card,
        body.theme-dark .elms-download-card,
        body.dark .elms-download-card,
        [data-theme="dark"] .elms-download-card,
        [data-bs-theme="dark"] .elms-download-card,
        .theme-dark .elms-download-card {
            background: #0f172a !important;
            border-color: #334155 !important;
        }
        body.dark-theme .elms-notes-box,
        body.dark-mode .elms-notes-box,
        body.theme-dark .elms-notes-box,
        body.dark .elms-notes-box,
        [data-theme="dark"] .elms-notes-box,
        [data-bs-theme="dark"] .elms-notes-box,
        .theme-dark .elms-notes-box {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #cbd5e1 !important;
        }
    </style>

    <div class="elms-card">
        <!-- Modern Card Header (Version badge aligned with Status on the right) -->
        <div class="elms-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="elms-title">
                <i class="fas fa-shield-alt text-primary"></i> Software License Details
            </h4>
            <div class="d-flex align-items-center gap-2">
                {if $latest_version}
                    <span class="elms-version-badge">v{$latest_version}</span>
                {/if}
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
                            {if $license_key}{$license_key}{else}<span style="color:#94a3b8; font-size:14px; font-weight:normal;">Generating license key...</span>{/if}
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
            <div class="mt-4 pt-3 border-top" style="border-color: rgba(226, 232, 240, 0.6) !important;">
                <div class="elms-download-card d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-bold fs-6 d-flex align-items-center gap-2" style="color: inherit;">
                            <i class="fas fa-box-open text-primary"></i> Software Package & Updates
                            {if $latest_version}
                                <span class="elms-version-badge">v{$latest_version}</span>
                            {/if}
                        </div>
                        <div class="small mt-1" style="color: #64748b;">
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
                    <div class="elms-notes-box mt-3 p-3 rounded">
                        <div class="fw-bold small mb-2 d-flex align-items-center gap-1" style="color: inherit;">
                            <i class="fas fa-info-circle text-info"></i> What's New {if $latest_version}(v{$latest_version}){/if}:
                        </div>
                        <div class="small" style="white-space: pre-line; line-height: 1.6; font-family: inherit; color: inherit;">{$update_notes}</div>
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
