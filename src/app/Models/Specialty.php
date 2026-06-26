<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

class Specialty extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ========== Media Library ==========
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('specialty_icon')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(100)
                    ->height(100)
                    ->fit(Fit::Crop, 100, 100)
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
    }

    // ========== Accessors ==========
    public function getIconUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('specialty_icon');
        return $media ? $media->getUrl() : null;
    }

    public function getIconThumbAttribute(): ?string
    {
        $media = $this->getFirstMedia('specialty_icon');
        return $media ? $media->getUrl('thumb') : null;
    }

    public function getIconMediumAttribute(): ?string
    {
        $media = $this->getFirstMedia('specialty_icon');
        return $media ? $media->getUrl('medium') : null;
    }

    public function getIconLargeAttribute(): ?string
    {
        $media = $this->getFirstMedia('specialty_icon');
        return $media ? $media->getUrl('large') : null;
    }

    // ========== Relationships ==========
    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'LIKE', "%{$term}%")
            ->orWhere('slug', 'LIKE', "%{$term}%");
    }

    // ========== Boot ==========
    protected static function booted()
    {
        static::deleting(function ($specialty) {
            $specialty->clearMediaCollection('specialty_icon');
        });
    }
}
