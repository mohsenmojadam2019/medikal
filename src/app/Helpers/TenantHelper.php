<?php

if (!function_exists('tenant_id')) {
    function tenant_id()
    {
        return session('tenant_id') ?? (auth()->user()->tenant_id ?? null);
    }
}

if (!function_exists('tenant')) {
    function tenant()
    {
        $tenantId = tenant_id();
        return $tenantId ? \App\Models\Tenant::find($tenantId) : null;
    }
}
