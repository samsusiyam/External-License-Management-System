<div class="card">
    <div class="card-header">
        <h3 class="card-title">My Licenses</h3>
    </div>
    <div class="card-body">
        {if $licenses}
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>License Key</th>
                        <th>Product</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th>Expiry</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $licenses as $license}
                        <tr>
                            <td><code>{$license.license_key}</code></td>
                            <td>{$license.product}</td>
                            <td>{$license.domain}</td>
                            <td>
                                {if $license.status eq 'active'}
                                    <span class="label label-success">Active</span>
                                {elseif $license.status eq 'suspended'}
                                    <span class="label label-warning">Suspended</span>
                                {elseif $license.status eq 'expired'}
                                    <span class="label label-default">Expired</span>
                                {else}
                                    <span class="label label-danger">{$license.status}</span>
                                {/if}
                            </td>
                            <td>{$license.expiry|default:'Never'}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {else}
            <p class="text-center text-muted">No licenses found for your account.</p>
        {/if}
    </div>
</div>
