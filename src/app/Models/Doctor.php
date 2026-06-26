<?php

namespace App\Models;

use App\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

class Doctor extends Model implements HasMedia
{
    use SoftDeletes, HasSeo, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'specialty_id',
        'license_number',
        'clinic_name',
        'clinic_address',
        'clinic_phone',
        'clinic_email',
        'latitude',
        'longitude',
        'bio',
        'experience_years',
        'consultation_fee',
        'visit_duration',
        'is_available',
        'is_verified',
        'is_active',
        'rating',
        'total_reviews',
        'working_hours',
        'education',
        'certificates',
        'social_links',
        'metadata',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'float',
        'consultation_fee' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
        'education' => 'array',
        'certificates' => 'array',
        'social_links' => 'array',
        'working_hours' => 'array',
        'metadata' => 'array',
    ];

    // ========== Media Library ==========
    public function registerMediaCollections(): void
    {
        // عکس پروفایل پزشک
        $this->addMediaCollection('profile_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(150)
                    ->height(150)
                    ->fit(Fit::Crop, 150, 150)
                    ->nonQueued();

                $this->addMediaConversion('medium')
                    ->width(300)
                    ->height(300)
                    ->fit(Fit::Crop, 300, 300)
                    ->nonQueued();

                $this->addMediaConversion('large')
                    ->width(600)
                    ->height(600)
                    ->fit(Fit::Crop, 600, 600)
                    ->nonQueued();
            });

        // گواهی‌های پزشک
        $this->addMediaCollection('certificates')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(200)
                    ->height(200)
                    ->fit(Fit::Crop, 200, 200)
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

    public function getCertificateUrlsAttribute(): array
    {
        return $this->getMedia('certificates')->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'name' => $media->file_name,
                'size' => $media->size,
            ];
        })->toArray();
    }

    // ========== Relationships ==========
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'doctor_patient')
            ->withTimestamps();
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function primaryAddress()
    {
        return $this->morphOne(Address::class, 'addressable')
            ->where('is_primary', true);
    }

    // ========== Accessors ==========
    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? 'دکتر';
    }

    public function getRatingDisplayAttribute(): string
    {
        return number_format($this->rating, 1);
    }

    public function getFullAddressAttribute()
    {
        $address = $this->primaryAddress;
        return $address ? $address->full_address : null;
    }

    public function getLocationAttribute(): ?array
    {
        if ($this->latitude && $this->longitude) {
            return [
                'lat' => $this->latitude,
                'lng' => $this->longitude,
            ];
        }
        return null;
    }

    // ========== Scopes ==========
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->whereHas('user', function ($user) use ($search) {
                $user->where('name', 'LIKE', "%{$search}%");
            })->orWhere('clinic_name', 'LIKE', "%{$search}%")
              ->orWhere('license_number', 'LIKE', "%{$search}%")
              ->orWhereHas('specialty', function ($s) use ($search) {
                  $s->where('name', 'LIKE', "%{$search}%");
              });
        });
    }

    public function scopeBySpecialty($query, $specialtyId)
    {
        return $query->where('specialty_id', $specialtyId);
    }

    public function scopeNearby($query, $lat, $lng, $radius = 10)
    {
        return $query->selectRaw("
            doctors.*,
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

    public function scopeByInsurance($query, $insuranceType)
    {
        return $query;
    }

    // ========== Methods ==========
    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    public function unverify(): void
    {
        $this->update(['is_verified' => false]);
    }

    public function toggleAvailability(): void
    {
        $this->update(['is_available' => !$this->is_available]);
    }

    public function calculateDistance($lat, $lng): float
    {
        if (!$this->latitude || !$this->longitude) {
            return -1;
        }

        $theta = $this->longitude - $lng;
        $dist = sin(deg2rad($this->latitude)) * sin(deg2rad($lat)) +
                cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles * 1.609344;
    }

    protected static function booted()
    {
        static::deleting(function ($doctor) {
            $doctor->clearMediaCollection('profile_image');
            $doctor->clearMediaCollection('certificates');
        });
    }
}
