<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pharmacy extends Model
{
    use SoftDeletes;

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

    // ========== Boot ==========
    protected static function booted()
    {
        static::creating(function ($pharmacy) {
            if (empty($pharmacy->slug)) {
                $pharmacy->slug = \Illuminate\Support\Str::slug($pharmacy->name);
            }
        });
    }
}
