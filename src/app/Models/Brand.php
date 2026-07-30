<?php

// app/Models/Brand.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Brand extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'website',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ============================================
    // ✅ Media Library
    // ============================================

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('brand_logo')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(100)
                    ->height(100)
                    ->fit(\Spatie\Image\Enums\Fit::Crop, 100, 100);
            });
    }

    // ============================================
    // ✅ Accessors
    // ============================================

    public function getLogoUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('brand_logo');
        return $media ? $media->getUrl() : null;
    }

    public function getLogoThumbAttribute(): ?string
    {
        $media = $this->getFirstMedia('brand_logo');
        return $media ? $media->getUrl('thumb') : null;
    }

    // ============================================
    // ✅ Relationships
    // ============================================

    public function products()
    {
        return $this->hasMany(Product::class);
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
        static::creating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
            if (empty($brand->tenant_id)) {
                $brand->tenant_id = session('tenant_id', 1);
            }
        });

        static::deleting(function ($brand) {
            $brand->clearMediaCollection('brand_logo');
        });
    }
}
