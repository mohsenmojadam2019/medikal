<?php
// app/Models/ProductTag.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductTag extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ============================================
    // ✅ Relationships
    // ============================================

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tag');
    }

    // ============================================
    // ✅ Scopes
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ============================================
    // ✅ Boot
    // ============================================

    protected static function booted()
    {
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
            if (empty($tag->tenant_id)) {
                $tag->tenant_id = session('tenant_id', 1);
            }
        });
    }
}
