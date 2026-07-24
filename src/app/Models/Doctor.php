<?php
// app/Models/Doctor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

class Doctor extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'clinic_id',
        'province_id',
        'city_id',
        'specialty_id',
        'license_number',
        // ❌ حذف شد - profile_image (از Media Library استفاده می‌شود)
        'latitude',
        'longitude',
        'bio',
        'biography',
        'education',
        'certificates',
        'social_links',
        'working_hours',
        'experience_years',
        'consultation_fee',
        'appointment_fee_type',
        'appointment_fee_amount',
        'is_fee_editable_by_admin',
        'visit_duration',
        'is_available',
        'is_verified',
        'is_active',
        'rating',
        'total_reviews',
        'metadata',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'social_links' => 'array',
        'metadata' => 'array',
        'experience_years' => 'integer',
        'consultation_fee' => 'decimal:2',
        'appointment_fee_amount' => 'decimal:2',
        'is_available' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'is_fee_editable_by_admin' => 'boolean',
        'rating' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // ========== Media Library ==========

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(150)
                    ->height(150)
                    ->fit(Fit::Crop, 150, 150)
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
    }

    // ========== Accessors ==========

    public function getProfileImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('profile_image');
        return $media ? $media->getUrl() : null;
    }

    public function getProfileImageThumbAttribute(): ?string
    {
        $media = $this->getFirstMedia('profile_image');
        return $media ? $media->getUrl('thumb') : null;
    }

    public function getProfileImageMediumAttribute(): ?string
    {
        $media = $this->getFirstMedia('profile_image');
        return $media ? $media->getUrl('medium') : null;
    }

    public function getProfileImageLargeAttribute(): ?string
    {
        $media = $this->getFirstMedia('profile_image');
        return $media ? $media->getUrl('large') : null;
    }

    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? $this->name ?? 'پزشک';
    }

    public function getFullAddressAttribute(): string
    {
        if ($this->clinic) {
            return $this->clinic->full_address;
        }

        $parts = [];
        if ($this->city) $parts[] = $this->city->name;
        if ($this->province) $parts[] = $this->province->name;
        return implode('، ', $parts);
    }

    public function getClinicNameAttribute(): string
    {
        return $this->clinic?->name ?? 'بدون کلینیک';
    }

    public function getClinicAddressAttribute(): string
    {
        return $this->clinic?->address ?? '';
    }

    public function getClinicPhoneAttribute(): string
    {
        return $this->clinic?->phone ?? '';
    }

    public function getClinicEmailAttribute(): string
    {
        return $this->clinic?->email ?? '';
    }

    public function getAppointmentFeeLabelAttribute(): string
    {
        if ($this->appointment_fee_type === 'free') {
            return 'رایگان';
        }
        return number_format($this->appointment_fee_amount ?? 0) . ' تومان';
    }

    public function getAppointmentFeeValueAttribute(): float
    {
        if ($this->appointment_fee_type === 'free') {
            return 0;
        }
        return (float) ($this->appointment_fee_amount ?? $this->consultation_fee ?? 0);
    }

    public function isFreeAppointment(): bool
    {
        return $this->appointment_fee_type === 'free';
    }

    public function getFeeForAppointment(): float
    {
        if ($this->appointment_fee_type === 'free') {
            return 0;
        }
        return (float) ($this->appointment_fee_amount ?? $this->consultation_fee ?? 0);
    }

    // ========== Scopes ==========

    public function scopeFreeAppointments($query)
    {
        return $query->where('appointment_fee_type', 'free');
    }

    public function scopePaidAppointments($query)
    {
        return $query->where('appointment_fee_type', 'paid');
    }

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('license_number', 'LIKE', "%{$term}%")
                ->orWhereHas('user', function ($q2) use ($term) {
                    $q2->where('name', 'LIKE', "%{$term}%")
                        ->orWhere('mobile', 'LIKE', "%{$term}%");
                })
                ->orWhereHas('clinic', function ($q2) use ($term) {
                    $q2->where('name', 'LIKE', "%{$term}%");
                });
        });
    }

    public function scopeBySpecialty($query, $specialtyId)
    {
        return $query->where('specialty_id', $specialtyId);
    }

    public function scopeByClinic($query, $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
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

    public function toggleAvailability(): void
    {
        $this->update(['is_available' => !$this->is_available]);
    }

    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    public function unverify(): void
    {
        $this->update(['is_verified' => false]);
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}
