<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'addressable_type',
        'addressable_id',
        'province_id',
        'city_id',
        'address_line_1',
        'address_line_2',
        'neighborhood',
        'street',
        'alley',
        'plaque',
        'unit',
        'postal_code',
        'phone',
        'mobile',
        'latitude',
        'longitude',
        'type',
        'is_primary',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function addressable()
    {
        return $this->morphTo();
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFullAddressAttribute()
    {
        $parts = [];
        if ($this->address_line_1) $parts[] = $this->address_line_1;
        if ($this->address_line_2) $parts[] = $this->address_line_2;
        if ($this->neighborhood) $parts[] = 'محله ' . $this->neighborhood;
        if ($this->city) $parts[] = $this->city->name;
        if ($this->province) $parts[] = $this->province->name;
        return implode('، ', $parts);
    }
}
