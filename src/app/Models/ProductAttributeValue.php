<?php

// app/Models/ProductAttributeValue.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    protected $table = 'product_attribute_values';

    protected $fillable = [
        'attribute_id',
        'product_id',
        'value',
    ];

    // ============================================
    // ✅ Relationships
    // ============================================

    /**
     * ویژگی مربوطه
     */
    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class);
    }

    /**
     * محصول مربوطه
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ============================================
    // ✅ Accessors
    // ============================================

    /**
     * دریافت نام ویژگی
     */
    public function getAttributeNameAttribute(): string
    {
        return $this->attribute->name ?? 'نامشخص';
    }

    /**
     * دریافت نوع ویژگی
     */
    public function getAttributeTypeAttribute(): string
    {
        return $this->attribute->type ?? 'text';
    }

    // ============================================
    // ✅ Scopes
    // ============================================

    /**
     * فیلتر بر اساس ویژگی
     */
    public function scopeByAttribute($query, $attributeId)
    {
        return $query->where('attribute_id', $attributeId);
    }

    /**
     * فیلتر بر اساس محصول
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * فیلتر بر اساس مقدار
     */
    public function scopeByValue($query, $value)
    {
        return $query->where('value', $value);
    }

    // ============================================
    // ✅ Methods
    // ============================================

    /**
     * بررسی اینکه مقدار با یک جستجو مطابقت دارد
     */
    public function matchesSearch($keyword): bool
    {
        return stripos($this->value, $keyword) !== false;
    }
}
