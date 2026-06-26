<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drug extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'generic_name', 'code', 'category', 'form',
        'strength', 'manufacturer', 'requires_prescription',
        'is_active', 'metadata'
    ];

    protected $casts = [
        'requires_prescription' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function prescriptionItems()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(PharmacyOrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'LIKE', "%{$term}%")
            ->orWhere('generic_name', 'LIKE', "%{$term}%")
            ->orWhere('code', 'LIKE', "%{$term}%");
    }
}
