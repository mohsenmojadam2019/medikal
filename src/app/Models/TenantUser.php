<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUser extends Model
{
    protected $table = 'tenant_users';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'role',
        'is_active',
        'is_primary',
        'joined_at',
        'permissions',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'joined_at' => 'datetime',
        'permissions' => 'array',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ========== Accessors ==========
    public function getRoleLabelAttribute(): string
    {
        $labels = [
            'super_admin' => 'مدیر کل',
            'clinic_admin' => 'مدیر کلینیک',
            'doctor' => 'پزشک',
            'receptionist' => 'منشی',
            'patient' => 'بیمار',
        ];
        return $labels[$this->role] ?? $this->role;
    }

    public function getRoleColorAttribute(): string
    {
        $colors = [
            'super_admin' => 'danger',
            'clinic_admin' => 'primary',
            'doctor' => 'success',
            'receptionist' => 'info',
            'patient' => 'secondary',
        ];
        return $colors[$this->role] ?? 'secondary';
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ========== Methods ==========
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }
}
