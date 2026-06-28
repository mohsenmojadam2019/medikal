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
    ];

    public function contracts()
    {
        return $this->hasMany(PharmacyContract::class);
    }

    public function orders()
    {
        return $this->hasMany(PharmacyOrder::class);
    }

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
}
