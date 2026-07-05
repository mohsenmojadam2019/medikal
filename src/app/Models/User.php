<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia, HasRoles;

    protected $fillable = [
        'name',
        'full_name',
        'email',
        'mobile',
        'password',
        'is_active',
        'is_super_admin',
        'last_login_at',
        'last_login_ip',
        'metadata',
        'language',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_super_admin' => 'boolean',
        'metadata' => 'array',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->useFallbackUrl('/images/default-avatar.png')
            ->useFallbackPath(public_path('/images/default-avatar.png'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->fit(Fit::Crop, 100, 100)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(300)
            ->height(300)
            ->fit(Fit::Crop, 300, 300)
            ->sharpen(10)
            ->nonQueued();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb');
    }

    public function getAvatarMediumUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'medium');
    }

    public function isAdmin(): bool
    {
        return $this->is_super_admin || $this->hasRole('admin') || $this->hasRole('super_admin');
    }
}
