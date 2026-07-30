<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Str;

class Product extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'pharmacy_id',
        'brand_id',
        'name',
        'slug',
        'generic_name',
        'code',
        'category',
        'form',
        'strength',
        'manufacturer',
        'country_of_origin',
        'license_from',
        'product_form',
        'volume',
        'container_type',
        'container_material',
        'weight',
        'dimensions',
        'price',
        'discount_percent',
        'discounted_price',
        'has_discount',
        'stock',
        'requires_prescription',
        'is_active',
        'short_description',
        'description',
        'product_features',
        'usage_instructions',
        'tags',
        'avg_rating',
        'review_count',
        'allow_reviews',
        'metadata',
    ];

    protected $casts = [
        'requires_prescription' => 'boolean',
        'is_active' => 'boolean',
        'has_discount' => 'boolean',
        'allow_reviews' => 'boolean',
        'price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'stock' => 'integer',
        'avg_rating' => 'float',
        'review_count' => 'integer',
        'product_features' => 'array',
        'usage_instructions' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    // ============================================
    // ✅ Relationships
    // ============================================

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories()
    {
        return $this->belongsToMany(
            ProductCategory::class,
            'category_product',
            'product_id',
            'category_id'
        )->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryCategory()
    {
        return $this->belongsToMany(
            ProductCategory::class,
            'category_product',
            'product_id',
            'category_id'
        )->wherePivot('is_primary', true)
            ->withTimestamps();
    }

    public function attributeValues()
    {
        return $this->hasMany(ProductAttributeValue::class, 'product_id', 'id');
    }

    public function attributes()
    {
        return $this->belongsToMany(
            ProductAttribute::class,
            'product_attribute_values',
            'product_id',
            'attribute_id'
        )->withPivot('value')
            ->withTimestamps();
    }

    // ✅ اصلاح شده - رابطه tags با مشخص کردن فیلدها
    public function tags()
    {
        return $this->belongsToMany(
            ProductTag::class,
            'product_tag',
            'product_id',
            'tag_id'
        )->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews()
    {
        return $this->reviews()->where('is_approved', true);
    }

    public function pharmacyOrderItems()
    {
        return $this->hasMany(PharmacyOrderItem::class);
    }

    public function prescriptionItems()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    // ============================================
    // ✅ Accessors
    // ============================================

    public function getDiscountedPriceAttribute()
    {
        if ($this->has_discount && $this->discount_percent > 0) {
            return round($this->price - ($this->price * $this->discount_percent / 100), 2);
        }
        return $this->price;
    }

    public function getDisplayPriceAttribute(): array
    {
        if ($this->has_discount && $this->discount_percent > 0) {
            return [
                'original' => $this->price,
                'discounted' => $this->discounted_price,
                'discount_percent' => $this->discount_percent,
                'saved' => round($this->price - $this->discounted_price, 2),
                'is_on_sale' => true,
            ];
        }
        return [
            'original' => $this->price,
            'discounted' => $this->price,
            'discount_percent' => 0,
            'saved' => 0,
            'is_on_sale' => false,
        ];
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) return 'ناموجود';
        if ($this->stock < 10) return 'کمتر از ۱۰';
        if ($this->stock < 50) return 'موجود';
        return 'موجود کامل';
    }

    public function getStockStatusColorAttribute(): string
    {
        if ($this->stock <= 0) return 'danger';
        if ($this->stock < 10) return 'warning';
        if ($this->stock < 50) return 'info';
        return 'success';
    }

    public function getAverageRatingAttribute(): float
    {
        if ($this->review_count > 0) {
            return round($this->reviews()->approved()->avg('rating') ?? 0, 1);
        }
        return 0;
    }

    public function getApprovedReviewsCountAttribute(): int
    {
        return $this->reviews()->approved()->count();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name . ($this->strength ? " ({$this->strength})" : '');
    }

    public function getFeatureListAttribute(): array
    {
        return $this->product_features ?? [];
    }

    public function getTagsListAttribute(): array
    {
        return $this->tags->pluck('name')->toArray();
    }

    public function getAttributesGroupedAttribute(): array
    {
        return $this->attributeValues()->get()->groupBy('attribute.name')
            ->map(function ($values) {
                return $values->pluck('value')->implode('، ');
            })
            ->toArray();
    }

    // ============================================
    // ✅ Scopes
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeOnSale($query)
    {
        return $query->where('has_discount', true)
            ->where('discount_percent', '>', 0);
    }

    public function scopeRequiresPrescription($query)
    {
        return $query->where('requires_prescription', true);
    }

    public function scopeOverTheCounter($query)
    {
        return $query->where('requires_prescription', false);
    }

    public function scopeByPharmacy($query, $pharmacyId)
    {
        return $query->where('pharmacy_id', $pharmacyId);
    }

    public function scopeByBrand($query, $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    public function scopeInCategory($query, $categoryId)
    {
        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('generic_name', 'LIKE', "%{$term}%")
                ->orWhere('code', 'LIKE', "%{$term}%")
                ->orWhere('manufacturer', 'LIKE', "%{$term}%");
        });
    }

    // ============================================
    // ✅ Methods
    // ============================================

    public function generateCode(): string
    {
        $prefix = 'PRD';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $code = "{$prefix}-{$year}{$month}-{$random}";

        while (self::where('code', $code)->exists()) {
            $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $code = "{$prefix}-{$year}{$month}-{$random}";
        }

        return $code;
    }

    public static function generateUniqueSlug($name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function decreaseStock(int $quantity): bool
    {
        if ($this->stock < $quantity) {
            return false;
        }

        $this->decrement('stock', $quantity);
        return true;
    }

    public function increaseStock(int $quantity): void
    {
        $this->increment('stock', $quantity);
    }

    public function recalculateRating(): void
    {
        $avg = $this->reviews()->approved()->avg('rating') ?? 0;
        $count = $this->reviews()->approved()->count();

        $this->update([
            'avg_rating' => round($avg, 1),
            'review_count' => $count,
        ]);
    }

    protected static function booted()
    {
        static::creating(function ($product) {
            if (empty($product->code)) {
                $product->code = $product->generateCode();
            }
            if (empty($product->slug)) {
                $product->slug = self::generateUniqueSlug($product->name);
            }
            if (empty($product->tenant_id)) {
                $product->tenant_id = session('tenant_id', 1);
            }
            if (empty($product->pharmacy_id)) {
                $product->pharmacy_id = session('pharmacy_id', 1);
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && !$product->isDirty('slug')) {
                $product->slug = self::generateUniqueSlug($product->name);
            }

            if ($product->isDirty('price') && $product->has_discount) {
                $product->discounted_price = $product->price - ($product->price * $product->discount_percent / 100);
            }
        });
    }
}
