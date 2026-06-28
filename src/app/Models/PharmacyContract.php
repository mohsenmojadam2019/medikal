<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmacyContract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'clinic_id', 'pharmacy_id', 'type', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
