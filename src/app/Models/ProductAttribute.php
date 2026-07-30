<?php

// app/Models/ProductAttribute.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductAttribute extends Model
{
    use SoftDeletes;

    protected $table = 'product_attributes';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'type',
        'options',
        'is_filterable',
        'order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_filterable' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // ============================================
    // ✅ Relationships
    // ============================================

    /**
     * ✅ مقادیر این ویژگی برای محصولات مختلف
     * اصلاح شده با مشخص کردن فیلدهای خارجی
     */
    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id', 'id');
    }

    /**
     * محصولاتی که این ویژگی را دارند
     */
    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_attribute_values',
            'attribute_id',
            'product_id'
        )->withPivot('value')
            ->withTimestamps();
    }

    // ============================================
    // ✅ Accessors
    // ============================================

    /**
     * دریافت نوع به صورت فارسی
     */
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'text' => 'متن',
            'select' => 'انتخابی',
            'number' => 'عدد',
            'color' => 'رنگ',
            'checkbox' => 'چک‌باکس',
            'radio' => 'رادیویی',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    /**
     * دریافت گزینه‌ها به صورت آرایه
     */
    public function getOptionsArrayAttribute(): array
    {
        return is_array($this->options) ? $this->options : [];
    }

    /**
     * دریافت گزینه‌ها برای نمایش در فرانت‌اند
     */
    public function getOptionsForSelectAttribute(): array
    {
        $options = $this->options_array;
        $result = [];

        foreach ($options as $option) {
            $result[] = [
                'label' => $option,
                'value' => $option,
            ];
        }

        return $result;
    }

    // ============================================
    // ✅ Scopes
    // ============================================

    /**
     * ویژگی‌های فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ویژگی‌های قابل فیلتر
     */
    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    /**
     * ویژگی‌های با نوع خاص
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * جستجو بر اساس نام
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where('name', 'LIKE', "%{$keyword}%");
    }

    // ============================================
    // ✅ Methods
    // ============================================

    /**
     * بررسی اینکه آیا ویژگی دارای گزینه‌های انتخابی است
     */
    public function hasOptions(): bool
    {
        return in_array($this->type, ['select', 'radio', 'checkbox']) && !empty($this->options);
    }

    /**
     * بررسی اینکه آیا ویژگی قابل فیلتر است
     */
    public function isFilterable(): bool
    {
        return $this->is_filterable;
    }

    /**
     * فعال کردن ویژگی
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * غیرفعال کردن ویژگی
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * دریافت مقادیر یک محصول خاص
     */
    public function getValueForProduct($productId): ?string
    {
        $value = $this->values()->where('product_id', $productId)->first();
        return $value ? $value->value : null;
    }

    /**
     * دریافت تمام مقادیر موجود برای این ویژگی (برای فیلتر)
     */
    public function getAvailableValues(): array
    {
        return $this->values()
            ->select('value')
            ->distinct()
            ->pluck('value')
            ->toArray();
    }

    /**
     * ایجاد اسلاگ منحصر‌به‌فرد
     */
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

    // ============================================
    // ✅ Boot
    // ============================================

    protected static function booted()
    {
        static::creating(function ($attribute) {
            if (empty($attribute->slug)) {
                $attribute->slug = self::generateUniqueSlug($attribute->name);
            }
            if (empty($attribute->tenant_id)) {
                $attribute->tenant_id = session('tenant_id', 1);
            }
        });

        static::updating(function ($attribute) {
            if ($attribute->isDirty('name') && !$attribute->isDirty('slug')) {
                $attribute->slug = self::generateUniqueSlug($attribute->name);
            }
        });

        static::deleting(function ($attribute) {
            // حذف تمام مقادیر مرتبط
            $attribute->values()->delete();
        });
    }
}
