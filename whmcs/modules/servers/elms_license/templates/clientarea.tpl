<div class="elms-license-wrapper mb-4" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <style>
        /* -------------------------------------------------------------
           LIGHT THEME (Default)
        ------------------------------------------------------------- */
        .elms-license-wrapper .elms-card {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03) !important;
            overflow: hidden !important;
            color: #0f172a !important;
        }
        .elms-license-wrapper .elms-header {
            background: #ffffff !important;
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 18px 24px !important;
        }
        .elms-license-wrapper .elms-title {
            color: #0f172a !important;
            font-size: 1.125rem !important;
            font-weight: 700 !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin: 0 !important;
        }
        .elms-license-wrapper .elms-version-badge {
            background: #f1f5f9 !important;
            color: #334155 !important;
            border: 1px solid #cbd5e1 !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            padding: 3px 9px !important;
            border-radius: 14px !important;
            letter-spacing: 0.3px !important;
            display: inline-flex !important;
            align-items: center !important;
        }
        .elms-license-wrapper .elms-status-badge {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            padding: 4px 12px !important;
            border-radius: 20px !important;
            letter-spacing: 0.3px !important;
        }
        .elms-license-wrapper .elms-status-active {
            background: #ecfdf5 !important;
            color: #047857 !important;
            border: 1px solid #a7f3d0 !important;
        }
        .elms-license-wrapper .elms-status-suspended {
            background: #fefce8 !important;
            color: #854d0e !important;
            border: 1px solid #fef08a !important;
        }
        .elms-license-wrapper .elms-status-expired {
            background: #fff7ed !important;
            color: #9a3412 !important;
            border: 1px solid #fed7aa !important;
        }
        .elms-license-wrapper .elms-status-terminated {
            background: #fef2f2 !important;
            color: #991b1b !important;
            border: 1px solid #fecaca !important;
        }
        .elms-license-wrapper .elms-status-pending {
            background: #f8fafc !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
        }
        .elms-license-wrapper .elms-dot {
            width: 8px !important;
            height: 8px !important;
            border-radius: 50% !important;
            display: inline-block !important;
        }
        .elms-license-wrapper .elms-status-active .elms-dot { background: #10b981 !important; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25) !important; }
        .elms-license-wrapper .elms-status-suspended .elms-dot { background: #eab308 !important; }
        .elms-license-wrapper .elms-status-expired .elms-dot { background: #ea580c !important; }
        .elms-license-wrapper .elms-status-terminated .elms-dot { background: #ef4444 !important; }
        .elms-license-wrapper .elms-status-pending .elms-dot { background: #94a3b8 !important; }

        .elms-license-wrapper .elms-key-box {
            background: #0f172a !important;
            color: #ffffff !important;
            border-radius: 10px !important;
            padding: 18px 22px !important;
            border: 1px solid #1e293b !important;
        }
        .elms-license-wrapper .elms-key-label {
            font-size: 11px !important;
            font-weight: 700 !important;
            letter-spacing: 1px !important;
            color: #94a3b8 !important;
            text-transform: uppercase !important;
            margin-bottom: 6px !important;
        }
        .elms-license-wrapper .elms-key-value {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace !important;
            font-size: 1.2rem !important;
            font-weight: 700 !important;
            letter-spacing: 1.2px !important;
            color: #38bdf8 !important;
            word-break: break-all !important;
        }
        .elms-license-wrapper .elms-copy-btn {
            background: rgba(255, 255, 255, 0.12) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            padding: 7px 16px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            border-radius: 6px !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            text-decoration: none !important;
        }
        .elms-license-wrapper .elms-copy-btn:hover {
            background: rgba(255, 255, 255, 0.22) !important;
            color: #ffffff !important;
        }
        .elms-license-wrapper .elms-copy-btn.copied {
            background: #10b981 !important;
            border-color: #10b981 !important;
            color: #ffffff !important;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.4) !important;
        }

        .elms-license-wrapper .elms-prop-card {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 14px 18px !important;
            height: 100% !important;
            transition: border-color 0.2s ease !important;
        }
        .elms-license-wrapper .elms-prop-card:hover {
            border-color: #cbd5e1 !important;
        }
        .elms-license-wrapper .elms-prop-title {
            font-size: 12px !important;
            color: #64748b !important;
            font-weight: 500 !important;
            margin-bottom: 4px !important;
        }
        .elms-license-wrapper .elms-prop-val {
            font-size: 14px !important;
            font-weight: 700 !important;
            color: #0f172a !important;
        }

        .elms-license-wrapper .elms-download-card {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px !important;
            padding: 18px 22px !important;
        }
        .elms-license-wrapper .elms-dl-btn {
            background: #0284c7 !important;
            color: #ffffff !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            padding: 9px 20px !important;
            border-radius: 6px !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            transition: all 0.2s ease !important;
            border: 1px solid #0284c7 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        .elms-license-wrapper .elms-dl-btn:hover {
            background: #0369a1 !important;
            border-color: #0369a1 !important;
            color: #ffffff !important;
            text-decoration: none !important;
        }
        .elms-license-wrapper .elms-notes-box {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            color: #334155 !important;
            padding: 14px 18px !important;
            border-radius: 8px !important;
        }

        /* -------------------------------------------------------------
           DARK THEME (Applied when .is-dark is active OR Lagom Dark Classes)
        ------------------------------------------------------------- */
        .elms-license-wrapper.is-dark .elms-card,
        body.mode-dark .elms-card,
        html.mode-dark .elms-card,
        body.theme-dark .elms-card,
        html.theme-dark .elms-card,
        body.dark-mode .elms-card,
        body.dark-theme .elms-card,
        body.dark .elms-card,
        html.dark .elms-card,
        [data-theme="dark"] .elms-card,
        [data-mode="dark"] .elms-card,
        [data-scheme="dark"] .elms-card,
        [data-bs-theme="dark"] .elms-card {
            background: #181c24 !important;
            border-color: #2e3846 !important;
            color: #f8fafc !important;
        }
        .elms-license-wrapper.is-dark .elms-header,
        body.mode-dark .elms-header,
        html.mode-dark .elms-header,
        body.theme-dark .elms-header,
        html.theme-dark .elms-header,
        body.dark-mode .elms-header,
        body.dark-theme .elms-header,
        body.dark .elms-header,
        html.dark .elms-header,
        [data-theme="dark"] .elms-header,
        [data-mode="dark"] .elms-header,
        [data-scheme="dark"] .elms-header,
        [data-bs-theme="dark"] .elms-header {
            background: #181c24 !important;
            border-color: #2e3846 !important;
        }
        .elms-license-wrapper.is-dark .elms-title,
        body.mode-dark .elms-title,
        html.mode-dark .elms-title,
        body.theme-dark .elms-title,
        html.theme-dark .elms-title,
        body.dark-mode .elms-title,
        body.dark-theme .elms-title,
        body.dark .elms-title,
        html.dark .elms-title,
        [data-theme="dark"] .elms-title,
        [data-mode="dark"] .elms-title,
        [data-scheme="dark"] .elms-title,
        [data-bs-theme="dark"] .elms-title {
            color: #f8fafc !important;
        }
        .elms-license-wrapper.is-dark .elms-version-badge,
        body.mode-dark .elms-version-badge,
        html.mode-dark .elms-version-badge,
        body.theme-dark .elms-version-badge,
        html.theme-dark .elms-version-badge,
        body.dark-mode .elms-version-badge,
        body.dark-theme .elms-version-badge,
        body.dark .elms-version-badge,
        html.dark .elms-version-badge,
        [data-theme="dark"] .elms-version-badge,
        [data-mode="dark"] .elms-version-badge,
        [data-scheme="dark"] .elms-version-badge,
        [data-bs-theme="dark"] .elms-version-badge {
            background: #283344 !important;
            color: #93c5fd !important;
            border-color: #3b4b60 !important;
        }
        .elms-license-wrapper.is-dark .elms-prop-card,
        body.mode-dark .elms-prop-card,
        html.mode-dark .elms-prop-card,
        body.theme-dark .elms-prop-card,
        html.theme-dark .elms-prop-card,
        body.dark-mode .elms-prop-card,
        body.dark-theme .elms-prop-card,
        body.dark .elms-prop-card,
        html.dark .elms-prop-card,
        [data-theme="dark"] .elms-prop-card,
        [data-mode="dark"] .elms-prop-card,
        [data-scheme="dark"] .elms-prop-card,
        [data-bs-theme="dark"] .elms-prop-card {
            background: #1f2735 !important;
            border-color: #2e3846 !important;
        }
        .elms-license-wrapper.is-dark .elms-prop-title,
        body.mode-dark .elms-prop-title,
        html.mode-dark .elms-prop-title,
        body.theme-dark .elms-prop-title,
        html.theme-dark .elms-prop-title,
        body.dark-mode .elms-prop-title,
        body.dark-theme .elms-prop-title,
        body.dark .elms-prop-title,
        html.dark .elms-prop-title,
        [data-theme="dark"] .elms-prop-title,
        [data-mode="dark"] .elms-prop-title,
        [data-scheme="dark"] .elms-prop-title,
        [data-bs-theme="dark"] .elms-prop-title {
            color: #94a3b8 !important;
        }
        .elms-license-wrapper.is-dark .elms-prop-val,
        body.mode-dark .elms-prop-val,
        html.mode-dark .elms-prop-val,
        body.theme-dark .elms-prop-val,
        html.theme-dark .elms-prop-val,
        body.dark-mode .elms-prop-val,
        body.dark-theme .elms-prop-val,
        body.dark .elms-prop-val,
        html.dark .elms-prop-val,
        [data-theme="dark"] .elms-prop-val,
        [data-mode="dark"] .elms-prop-val,
        [data-scheme="dark"] .elms-prop-val,
        [data-bs-theme="dark"] .elms-prop-val {
            color: #f8fafc !important;
        }
        .elms-license-wrapper.is-dark .elms-download-card,
        body.mode-dark .elms-download-card,
        html.mode-dark .elms-download-card,
        body.theme-dark .elms-download-card,
        html.theme-dark .elms-download-card,
        body.dark-mode .elms-download-card,
        body.dark-theme .elms-download-card,
        body.dark .elms-download-card,
        html.dark .elms-download-card,
        [data-theme="dark"] .elms-download-card,
        [data-mode="dark"] .elms-download-card,
        [data-scheme="dark"] .elms-download-card,
        [data-bs-theme="dark"] .elms-download-card {
            background: #1f2735 !important;
            border-color: #2e3846 !important;
        }
        .elms-license-wrapper.is-dark .elms-notes-box,
        body.mode-dark .elms-notes-box,
        html.mode-dark .elms-notes-box,
        body.theme-dark .elms-notes-box,
        html.theme-dark .elms-notes-box,
        body.dark-mode .elms-notes-box,
        body.dark-theme .elms-notes-box,
        body.dark .elms-notes-box,
        html.dark .elms-notes-box,
        [data-theme="dark"] .elms-notes-box,
        [data-mode="dark"] .elms-notes-box,
        [data-scheme="dark"] .elms-notes-box,
        [data-bs-theme="dark"] .elms-notes-box {
            background: #1f2735 !important;
            border-color: #2e3846 !important;
            color: #cbd5e1 !important;
        }
    </style>

    <div class="elms-card">
        <!-- Card Header (Version badge next to status on the right) -->
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

            <!-- Properties Grid -->
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
(function() {
    function detectAndApplyElmsTheme() {
        var wrap = document.querySelector('.elms-license-wrapper');
        if (!wrap) return;

        var html = document.documentElement;
        var body = document.body;

        var isDark = body.classList.contains('mode-dark') ||
                     body.classList.contains('theme-dark') ||
                     body.classList.contains('dark') ||
                     body.classList.contains('dark-mode') ||
                     body.classList.contains('dark-theme') ||
                     html.classList.contains('mode-dark') ||
                     html.classList.contains('theme-dark') ||
                     html.classList.contains('dark') ||
                     html.getAttribute('data-theme') === 'dark' ||
                     html.getAttribute('data-mode') === 'dark' ||
                     html.getAttribute('data-scheme') === 'dark' ||
                     html.getAttribute('data-bs-theme') === 'dark' ||
                     body.getAttribute('data-theme') === 'dark' ||
                     body.getAttribute('data-mode') === 'dark' ||
                     body.getAttribute('data-scheme') === 'dark';

        if (!isDark) {
            try {
                var bg = window.getComputedStyle(body).backgroundColor;
                if (bg) {
                    var match = bg.match(/\d+/g);
                    if (match && match.length >= 3) {
                        var r = parseInt(match[0], 10);
                        var g = parseInt(match[1], 10);
                        var b = parseInt(match[2], 10);
                        var brightness = (r * 299 + g * 587 + b * 114) / 1000;
                        if (brightness < 80 && (r !== 0 || g !== 0 || b !== 0 || bg !== 'rgba(0, 0, 0, 0)')) {
                            isDark = true;
                        }
                    }
                }
            } catch (e) {}
        }

        if (isDark) {
            wrap.classList.add('is-dark');
        } else {
            wrap.classList.remove('is-dark');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', detectAndApplyElmsTheme);
    } else {
        detectAndApplyElmsTheme();
    }

    try {
        var observer = new MutationObserver(detectAndApplyElmsTheme);
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme', 'data-mode', 'data-scheme', 'data-bs-theme'] });
        observer.observe(document.body, { attributes: true, attributeFilter: ['class', 'data-theme', 'data-mode', 'data-scheme'] });
    } catch (e) {}
})();

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
