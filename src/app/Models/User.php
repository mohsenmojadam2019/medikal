<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasRoles, Notifiable, HasFactory, SoftDeletes, InteractsWithMedia;  // ✅ اضافه کردن InteractsWithMedia

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

    // ========== Media Library ==========
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(100)
                    ->height(100)
                    ->fit('crop', 100, 100)
                    ->nonQueued();

                $this->addMediaConversion('medium')
                    ->width(200)
                    ->height(200)
                    ->fit('crop', 200, 200)
                    ->nonQueued();

                $this->addMediaConversion('large')
                    ->width(400)
                    ->height(400)
                    ->fit('crop', 400, 400)
                    ->nonQueued();
            });
    }
    // ========== Accessors ==========
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar');
    }

    public function getAvatarThumbAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb');
    }

    public function getAvatarMediumAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'medium');
    }

    public function getAvatarLargeAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'large');
    }

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
