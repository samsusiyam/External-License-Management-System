<?php
/** @var string $doc_html */
/** @var string $current_doc */
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Documentation</h1>
        <p class="text-muted small mb-0">System architecture, API specifications, and WHMCS client integration tutorials.</p>
    </div>

    <!-- Navigation Pills / Tabs -->
    <div class="nav nav-pills bg-white border p-1 rounded-3 shadow-sm">
        <a class="nav-link py-1 px-3 small <?= $current_doc === 'guide' ? 'active' : '' ?>" href="<?= $base ?>/admin/docs?doc=guide">
            <i class="bi bi-journal-code me-1"></i> API Reference
        </a>
        <a class="nav-link py-1 px-3 small <?= $current_doc === 'whmcs' ? 'active' : '' ?>" href="<?= $base ?>/admin/docs?doc=whmcs">
            <i class="bi bi-box-seam me-1"></i> WHMCS Integration Guide
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi <?= $current_doc === 'whmcs' ? 'bi-shield-check text-success fs-5' : 'bi-book text-primary fs-5' ?>"></i>
            <div>
                <h5 class="mb-0 fs-6 fw-bold">
                    <?= $current_doc === 'whmcs' ? 'Host Nibo ELMS WHMCS Integration Guide' : 'ELMS REST API & Signature Specification' ?>
                </h5>
                <small class="text-muted">
                    <?= $current_doc === 'whmcs' ? 'Client Engine & Admin Gatekeeper setup' : 'Complete developer & self-hosting documentation' ?>
                </small>
            </div>
        </div>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print / PDF
            </button>
        </div>
    </div>
    <div class="card-body p-4 p-md-5">
        <div class="elms-doc" id="docContent">
            <?= $doc_html ?>
        </div>
    </div>
</div>

<style>
.elms-doc {
    font-size: 14.5px;
    line-height: 1.7;
    color: #334155;
}
.elms-doc h1, .elms-doc h2, .elms-doc h3, .elms-doc h4 {
    color: #0f172a;
    font-weight: 700;
}
.elms-doc h1 { font-size: 24px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; margin-top: 0; }
.elms-doc h2 { font-size: 19px; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; }
.elms-doc h3 { font-size: 16px; }
.elms-doc pre.elms-code {
    background: #0f172a;
    color: #f8fafc;
    border-radius: 8px;
    padding: 16px 20px;
    font-size: 13px;
    font-family: 'JetBrains Mono', Consolas, Monaco, monospace;
    overflow-x: auto;
    position: relative;
    border: 1px solid #1e293b;
}
.code-copy-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 4px 10px;
    font-size: 11px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.15);
    color: #f8fafc;
    border: 1px solid rgba(255, 255, 255, 0.25);
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 10;
}
.code-copy-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    color: #ffffff;
}
.elms-doc blockquote {
    background: #f8fafc;
    padding: 12px 18px;
    border-radius: 0 8px 8px 0;
    font-style: italic;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const codeBlocks = document.querySelectorAll('.code-block-wrapper');
    codeBlocks.forEach(function (wrapper) {
        const pre = wrapper.querySelector('pre');
        if (!pre) return;

        const copyBtn = document.createElement('button');
        copyBtn.className = 'code-copy-btn';
        copyBtn.innerHTML = '<i class="bi bi-clipboard me-1"></i> Copy';
        copyBtn.type = 'button';

        copyBtn.addEventListener('click', function () {
            const code = pre.querySelector('code');
            const textToCopy = code ? code.innerText : pre.innerText;
            navigator.clipboard.writeText(textToCopy).then(function () {
                copyBtn.innerHTML = '<i class="bi bi-check2 me-1"></i> Copied!';
                copyBtn.classList.add('bg-success', 'text-white');
                setTimeout(function () {
                    copyBtn.innerHTML = '<i class="bi bi-clipboard me-1"></i> Copy';
                    copyBtn.classList.remove('bg-success', 'text-white');
                }, 2000);
            }).catch(function () {
                copyBtn.innerText = 'Failed';
            });
        });

        wrapper.appendChild(copyBtn);
    });
});
</script>
