<?php
// app/Models/ProductCategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductCategory extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'parent_id',
        'order',
        'color',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'order' => 'integer',
    ];

    // ============================================
    // ✅ Media Library
    // ============================================

    public function registerMediaCollections(): void
    {
        // ✅ آیکون دسته‌بندی
        $this->addMediaCollection('category_icon')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('icon_thumb')
                    ->width(50)
                    ->height(50)
                    ->fit(\Spatie\Image\Enums\Fit::Crop, 50, 50);
            });

        // ✅ تصویر دسته‌بندی
        $this->addMediaCollection('category_image')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('image_thumb')
                    ->width(300)
                    ->height(300)
                    ->fit(\Spatie\Image\Enums\Fit::Crop, 300, 300);

                $this->addMediaConversion('image_medium')
                    ->width(600)
                    ->height(600)
                    ->fit(\Spatie\Image\Enums\Fit::Crop, 600, 600);
            });
    }

    // ============================================
    // ✅ Accessors - تصاویر
    // ============================================

    public function getIconUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('category_icon');
        return $media ? $media->getUrl() : null;
    }

    public function getIconThumbAttribute(): ?string
    {
        $media = $this->getFirstMedia('category_icon');
        return $media ? $media->getUrl('icon_thumb') : null;
    }

    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('category_image');
        return $media ? $media->getUrl() : null;
    }

    public function getImageThumbAttribute(): ?string
    {
        $media = $this->getFirstMedia('category_image');
        return $media ? $media->getUrl('image_thumb') : null;
    }

    // ============================================
    // ✅ Relationships
    // ============================================

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id')
            ->orderBy('order')
            ->orderBy('name');
    }

    /**
     * ✅ ارتباط با مدل Product
     */
    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'category_product',
            'category_id',
            'product_id'
        )->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryProducts()
    {
        return $this->belongsToMany(
            Product::class,
            'category_product',
            'category_id',
            'product_id'
        )->wherePivot('is_primary', true)
            ->withTimestamps();
    }

    public function activeProducts()
    {
        return $this->products()->where('is_active', true);
    }

    // ============================================
    // ✅ Accessors
    // ============================================

    public function getUrlAttribute(): string
    {
        return route('shop.category', $this->slug);
    }

    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->name . ' › ' . $this->name;
        }
        return $this->name;
    }

    public function getDepthAttribute(): int
    {
        $depth = 0;
        $parent = $this->parent;
        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }
        return $depth;
    }

    // ============================================
    // ✅ Scopes
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'LIKE', "%{$keyword}%")
                ->orWhere('description', 'LIKE', "%{$keyword}%")
                ->orWhere('meta_title', 'LIKE', "%{$keyword}%");
        });
    }

    // ============================================
    // ✅ Methods
    // ============================================

    public function getAllChildren(): array
    {
        $children = [];
        foreach ($this->children as $child) {
            $children[] = $child;
            $children = array_merge($children, $child->getAllChildren());
        }
        return $children;
    }

    public function getAllChildrenIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllChildrenIds());
        }
        return $ids;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getTotalProductsCount(): int
    {
        $categoryIds = $this->getAllChildrenIds();
        $categoryIds[] = $this->id;

        return Product::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('category_id', $categoryIds);
        })->where('is_active', true)->count();
    }

    public static function generateUniqueSlug($name, $parentId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)
            ->where('parent_id', $parentId)
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // ============================================
    // ✅ Boot
    // ============================================

    protected static function booted()
    {
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = self::generateUniqueSlug($category->name, $category->parent_id);
            }

            if (empty($category->tenant_id)) {
                $category->tenant_id = session('tenant_id', 1);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = self::generateUniqueSlug($category->name, $category->parent_id);
            }
        });

        static::saving(function ($category) {
            if ($category->parent_id && $category->parent_id == $category->id) {
                throw new \Exception('دسته نمی‌تواند خودش را به عنوان والد داشته باشد.');
            }
        });

        static::deleting(function ($category) {
            $category->clearMediaCollection('category_icon');
            $category->clearMediaCollection('category_image');
        });
    }
}
