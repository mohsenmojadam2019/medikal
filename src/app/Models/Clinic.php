<?php
// app/Models/Clinic.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

class Clinic extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'province_id',
        'city_id',
        'address',
        'phone',
        'email',
        'website',
        // ❌ حذف شد - logo (از Media Library استفاده می‌شود)
        // ❌ حذف شد - favicon (از Media Library استفاده می‌شود)
        'latitude',
        'longitude',
        'timezone',
        'currency',
        'language',
        'tax_rate',
        'invoice_prefix',
        'appointment_prefix',
        'primary_color',
        'secondary_color',
        'theme',
        'is_active',
        'is_verified',
        'webhook_enabled',
        'webhook_secret',
        'webhook_logs',
        'metadata',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'webhook_enabled' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'tax_rate' => 'decimal:2',
        'webhook_logs' => 'array',
        'metadata' => 'array',
        'settings' => 'array',
    ];

    // ========== Media Library ==========

    public function registerMediaCollections(): void
    {
        // ✅ کالکشن لوگو
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
            ->registerMediaConversions(function (Media $media) {
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

                $this->addMediaConversion('large')
                    ->width(600)
                    ->height(600)
                    ->fit(Fit::Crop, 600, 600)
                    ->sharpen(10)
                    ->nonQueued();
            });

        // ✅ کالکشن favicon
        $this->addMediaCollection('favicon')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/x-icon'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('favicon')
                    ->width(32)
                    ->height(32)
                    ->fit(Fit::Crop, 32, 32)
                    ->sharpen(10)
                    ->nonQueued();
            });
    }

    // ========== Accessors ==========

    // ✅ لوگو
    public function getLogoUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('logo');
        return $media ? $media->getUrl() : null;
    }

    public function getLogoThumbAttribute(): ?string
    {
        $media = $this->getFirstMedia('logo');
        return $media ? $media->getUrl('thumb') : null;
    }

    public function getLogoMediumAttribute(): ?string
    {
        $media = $this->getFirstMedia('logo');
        return $media ? $media->getUrl('medium') : null;
    }

    public function getLogoLargeAttribute(): ?string
    {
        $media = $this->getFirstMedia('logo');
        return $media ? $media->getUrl('large') : null;
    }

    // ✅ favicon
    public function getFaviconUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('favicon');
        return $media ? $media->getUrl() : null;
    }

    public function getFaviconIconAttribute(): ?string
    {
        $media = $this->getFirstMedia('favicon');
        return $media ? $media->getUrl('favicon') : null;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [];
        if ($this->address) $parts[] = $this->address;
        if ($this->city) $parts[] = $this->city->name;
        if ($this->province) $parts[] = $this->province->name;
        return implode('، ', $parts);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'فعال' : 'غیرفعال';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'danger';
    }

    public function getIsVerifiedLabelAttribute(): string
    {
        return $this->is_verified ? 'تایید شده' : 'تایید نشده';
    }

    public function getWebhookStatusAttribute(): string
    {
        return $this->webhook_enabled ? 'فعال' : 'غیرفعال';
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('slug', 'LIKE', "%{$term}%")
                ->orWhere('address', 'LIKE', "%{$term}%")
                ->orWhere('phone', 'LIKE', "%{$term}%")
                ->orWhere('email', 'LIKE', "%{$term}%");
        });
    }

    public function scopeNearby($query, $lat, $lng, $radius = 10)
    {
        return $query->selectRaw("
            *,
            (6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            )) AS distance
        ", [$lat, $lng, $lat])
            ->having('distance', '<', $radius)
            ->orderBy('distance', 'asc');
    }

    // ========== Methods ==========

    public function generateSlug(): string
    {
        $slug = \Illuminate\Support\Str::slug($this->name);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    public function unverify(): void
    {
        $this->update(['is_verified' => false]);
    }

    public function enableWebhook(): void
    {
        $this->update(['webhook_enabled' => true]);
    }

    public function disableWebhook(): void
    {
        $this->update(['webhook_enabled' => false]);
    }

    public function generateWebhookSecret(): string
    {
        $secret = \Illuminate\Support\Str::random(32);
        $this->update(['webhook_secret' => $secret]);
        return $secret;
    }

    /**
     * آپلود لوگو
     */
    public function uploadLogo($file): self
    {
        $this->clearMediaCollection('logo');
        $this->addMedia($file)
            ->toMediaCollection('logo');
        return $this;
    }

    /**
     * حذف لوگو
     */
    public function deleteLogo(): self
    {
        $this->clearMediaCollection('logo');
        return $this;
    }

    /**
     * آپلود favicon
     */
    public function uploadFavicon($file): self
    {
        $this->clearMediaCollection('favicon');
        $this->addMedia($file)
            ->toMediaCollection('favicon');
        return $this;
    }

    /**
     * حذف favicon
     */
    public function deleteFavicon(): self
    {
        $this->clearMediaCollection('favicon');
        return $this;
    }

    // ========== Boot ==========

    protected static function booted()
    {
        static::creating(function ($clinic) {
            if (empty($clinic->slug)) {
                $clinic->slug = $clinic->generateSlug();
            }
        });

        // حذف فایل‌های مدیا هنگام حذف کلینیک
        static::deleting(function ($clinic) {
            $clinic->clearMediaCollection('logo');
            $clinic->clearMediaCollection('favicon');
        });
    }
}
