<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'province_id',
        'name',
        'slug',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }
}
