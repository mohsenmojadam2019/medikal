<?php
// app/Models/Pharmacy.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

class Pharmacy extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'license_number',
        'address',
        'phone',
        'email',
        'province_id',
        'city_id',
        'clinic_id',
        'latitude',
        'longitude',
        'working_hours',
        'is_active',
        'is_online',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'working_hours' => 'array',
        'metadata' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // ============================================
    // Media Library
    // ============================================

    public function registerMediaCollections(): void
    {
        // ✅ کالکشن لوگو داروخانه
        $this->addMediaCollection('pharmacy_logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('logo_thumb')
                    ->width(100)
                    ->height(100)
                    ->fit(Fit::Crop, 100, 100)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('logo_medium')
                    ->width(200)
                    ->height(200)
                    ->fit(Fit::Crop, 200, 200)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('logo_large')
                    ->width(400)
                    ->height(400)
                    ->fit(Fit::Crop, 400, 400)
                    ->sharpen(10)
                    ->nonQueued();
            });

        // ✅ کالکشن تصاویر گالری داروخانه
        $this->addMediaCollection('pharmacy_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(150)
                    ->height(150)
                    ->fit(Fit::Crop, 150, 150)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('medium')
                    ->width(400)
                    ->height(300)
                    ->fit(Fit::Crop, 400, 300)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('large')
                    ->width(800)
                    ->height(600)
                    ->fit(Fit::Crop, 800, 600)
                    ->sharpen(10)
                    ->nonQueued();
            });
    }

    // ============================================
    // Accessors - Images
    // ============================================

    // ✅ لوگو
    public function getLogoUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('pharmacy_logo');
        return $media ? $media->getUrl() : null;
    }

    public function getLogoThumbAttribute(): ?string
    {
        $media = $this->getFirstMedia('pharmacy_logo');
        return $media ? $media->getUrl('logo_thumb') : null;
    }

    public function getLogoMediumAttribute(): ?string
    {
        $media = $this->getFirstMedia('pharmacy_logo');
        return $media ? $media->getUrl('logo_medium') : null;
    }

    public function getLogoLargeAttribute(): ?string
    {
        $media = $this->getFirstMedia('pharmacy_logo');
        return $media ? $media->getUrl('logo_large') : null;
    }

    // ✅ گالری تصاویر
    public function getImagesAttribute()
    {
        return $this->getMedia('pharmacy_images');
    }

    public function getImagesUrlsAttribute(): array
    {
        return $this->getMedia('pharmacy_images')->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'medium' => $media->getUrl('medium'),
                'large' => $media->getUrl('large'),
                'name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'created_at' => $media->created_at->toDateTimeString(),
            ];
        })->toArray();
    }

    public function getFirstImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('pharmacy_images');
        return $media ? $media->getUrl() : null;
    }

    public function getFirstImageThumbAttribute(): ?string
    {
        $media = $this->getFirstMedia('pharmacy_images');
        return $media ? $media->getUrl('thumb') : null;
    }

    // ============================================
    // Relationships
    // ============================================

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function contracts()
    {
        return $this->hasMany(PharmacyContract::class);
    }

    public function orders()
    {
        return $this->hasMany(PharmacyOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ============================================
    // Accessors
    // ============================================

    public function getFullAddressAttribute()
    {
        $parts = [];
        if ($this->address) $parts[] = $this->address;
        if ($this->city) $parts[] = $this->city->name;
        if ($this->province) $parts[] = $this->province->name;
        return implode('، ', $parts);
    }

    public function getDistanceAttribute($value)
    {
        if (!$value) return null;

        if ($value < 1) {
            return round($value * 1000) . ' متر';
        }
        return number_format($value, 1) . ' کیلومتر';
    }

    // ============================================
    // Scopes
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
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

    public function scopeNearbyInMeters($query, $lat, $lng, $radiusInMeters = 10000)
    {
        $radiusInKm = $radiusInMeters / 1000;
        return $this->scopeNearby($query, $lat, $lng, $radiusInKm);
    }

    // ============================================
    // Helper Methods
    // ============================================

    public static function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public function isWithinRadius($lat, $lng, $radius = 10)
    {
        if (!$this->latitude || !$this->longitude) return false;
        $distance = self::calculateDistance($lat, $lng, $this->latitude, $this->longitude);
        return $distance <= $radius;
    }

    /**
     * آپلود لوگو
     */
    public function uploadLogo($file): self
    {
        $this->clearMediaCollection('pharmacy_logo');
        $this->addMedia($file)
            ->toMediaCollection('pharmacy_logo');
        return $this;
    }

    /**
     * حذف لوگو
     */
    public function deleteLogo(): self
    {
        $this->clearMediaCollection('pharmacy_logo');
        return $this;
    }

    /**
     * آپلود تصویر به گالری
     */
    public function uploadImage($file): self
    {
        $this->addMedia($file)
            ->toMediaCollection('pharmacy_images');
        return $this;
    }

    /**
     * حذف یک تصویر از گالری
     */
    public function deleteImage($mediaId): bool
    {
        $media = $this->getMedia('pharmacy_images')->where('id', $mediaId)->first();
        if ($media) {
            $media->delete();
            return true;
        }
        return false;
    }

    /**
     * حذف تمام تصاویر گالری
     */
    public function clearImages(): self
    {
        $this->clearMediaCollection('pharmacy_images');
        return $this;
    }

    // ========== Boot ==========
    protected static function booted()
    {
        static::creating(function ($pharmacy) {
            if (empty($pharmacy->slug)) {
                $pharmacy->slug = \Illuminate\Support\Str::slug($pharmacy->name);
            }
        });

        static::deleting(function ($pharmacy) {
            $pharmacy->clearMediaCollection('pharmacy_logo');
            $pharmacy->clearMediaCollection('pharmacy_images');
        });
    }
}
