<div class="card" style="margin-top:15px; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">
    <div class="card-header bg-primary text-white" style="padding:15px 20px;">
        <h3 class="card-title" style="margin:0; font-size:18px; font-weight:600;">
            <i class="fas fa-shield-alt" style="margin-right:8px;"></i> My Software Licenses
        </h3>
    </div>
    <div class="card-body" style="padding:20px;">
        {if $licenses && $licenses|@count > 0}
            <div class="table-responsive">
                <table class="table table-striped table-hover" style="vertical-align:middle;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th>Product</th>
                            <th>License Key</th>
                            <th>Domain</th>
                            <th>Status</th>
                            <th>Next Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $licenses as $license}
                            <tr>
                                <td><strong>{$license.product_name|default:$license.product_key}</strong></td>
                                <td>
                                    <div style="display:inline-flex; align-items:center; gap:8px;">
                                        <code style="font-size:13px; font-weight:bold; color:#0f172a; background:#f1f5f9; padding:4px 8px; border-radius:4px;">
                                            {$license.license_key}
                                        </code>
                                    </div>
                                </td>
                                <td>{$license.domain|default:'Any Domain'}</td>
                                <td>
                                    {if $license.status eq 'active'}
                                        <span class="label label-success" style="background:#22c55e; padding:4px 8px; border-radius:4px;">Active</span>
                                    {elseif $license.status eq 'suspended'}
                                        <span class="label label-warning" style="background:#f59e0b; padding:4px 8px; border-radius:4px;">Suspended</span>
                                    {elseif $license.status eq 'expired'}
                                        <span class="label label-default" style="background:#94a3b8; padding:4px 8px; border-radius:4px;">Expired</span>
                                    {else}
                                        <span class="label label-danger" style="background:#ef4444; padding:4px 8px; border-radius:4px;">{$license.status|ucfirst}</span>
                                    {/if}
                                </td>
                                <td>{$license.nextduedate|default:'Lifetime'}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {else}
            <div style="text-align:center; padding:40px; color:#64748b;">
                <p style="font-size:16px; margin:0;">No active software licenses found for your account.</p>
            </div>
        {/if}
    </div>
</div>
