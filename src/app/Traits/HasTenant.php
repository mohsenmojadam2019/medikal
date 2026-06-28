<?php

namespace App\Traits;

use App\Models\Tenant;

trait HasTenant
{
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeByTenant($query, $tenantId = null)
    {
        $tenantId = $tenantId ?? session('tenant_id');
        return $tenantId ? $query->where('tenant_id', $tenantId) : $query;
    }

    protected static function bootHasTenant()
    {
        static::creating(function ($model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = session('tenant_id') ?? auth()->user()->current_tenant_id ?? null;
            }
        });
    }
}
