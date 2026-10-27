<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Context;

if (!function_exists('tenant_id')) {
    function tenant_id(): int
    {
        $tenantId = Context::get('tenant_id');

        if (!is_numeric($tenantId)) {
            throw new RuntimeException(__('tenant.not_found'));
        }

        return (int) $tenantId;
    }
}

if (!function_exists('set_tenant')) {
    function set_tenant(int|Tenant $tenant): void
    {
        Context::add(
            'tenant_id',
            $tenant instanceof Tenant ? $tenant->id : $tenant
        );
    }
}
