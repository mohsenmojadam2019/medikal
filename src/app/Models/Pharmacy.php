<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pharmacy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name', 'license_number', 'address', 'phone', 'email',
        'latitude', 'longitude', 'working_hours', 'is_active',
        'is_online', 'metadata'
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

    /**
     * Scope برای پیدا کردن داروخانه‌های نزدیک
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $lat عرض جغرافیایی
     * @param float $lng طول جغرافیایی
     * @param float $radius شعاع بر حسب کیلومتر (پیش‌فرض ۱۰ کیلومتر)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNearby($query, $lat, $lng, $radius = 10)
    {
        // فرمول محاسبه فاصله (Haversine formula)
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

    /**
     * Scope برای پیدا کردن داروخانه‌های نزدیک با فاصله بر حسب متر
     */
    public function scopeNearbyInMeters($query, $lat, $lng, $radiusInMeters = 10000)
    {
        $radiusInKm = $radiusInMeters / 1000;
        return $this->scopeNearby($query, $lat, $lng, $radiusInKm);
    }

    // ============================================
    // Accessors
    // ============================================

    /**
     * دریافت فاصله به صورت فرمت‌شده
     */
    public function getDistanceAttribute($value)
    {
        if (!$value) return null;

        if ($value < 1) {
            return round($value * 1000) . ' متر';
        }
        return number_format($value, 1) . ' کیلومتر';
    }

    /**
     * دریافت آدرس کامل
     */
    public function getFullAddressAttribute()
    {
        $parts = [];
        if ($this->address) $parts[] = $this->address;
        if ($this->latitude && $this->longitude) {
            $parts[] = "({$this->latitude}, {$this->longitude})";
        }
        return implode(' - ', $parts);
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * محاسبه فاصله بین دو نقطه
     */
    public static function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // کیلومتر

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * بررسی اینکه داروخانه در شعاع مشخصی قرار دارد
     */
    public function isWithinRadius($lat, $lng, $radius = 10)
    {
        if (!$this->latitude || !$this->longitude) return false;

        $distance = self::calculateDistance($lat, $lng, $this->latitude, $this->longitude);
        return $distance <= $radius;
    }
}
