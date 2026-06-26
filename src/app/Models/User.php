<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'role',
        'is_active',
        'email_verified_at',
        'mobile_verified_at',
        'last_login_at',
        'last_login_ip',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function primaryAddress()
    {
        return $this->morphOne(Address::class, 'addressable')
            ->where('is_primary', true);
    }

    // ========== Role Check ==========
    public function isPatient(): bool
    {
        return $this->role === 'patient' || $this->hasRole('patient');
    }

    public function isDoctor(): bool
    {
        return $this->role === 'doctor' || $this->hasRole('doctor');
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']) || $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin' || $this->hasRole('super_admin');
    }

    // ========== Accessors ==========
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->mobile ?? $this->email ?? 'کاربر';
    }

    public function getFullAddressAttribute()
    {
        $address = $this->primaryAddress;
        return $address ? $address->full_address : null;
    }
}
