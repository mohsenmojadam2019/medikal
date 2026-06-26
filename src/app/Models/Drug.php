<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drug extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'generic_name',
        'code',
        'category',
        'form',
        'strength',
        'manufacturer',
        'requires_prescription',
        'price',
        'stock',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'requires_prescription' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'stock' => 'integer',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function prescriptionItems()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function pharmacyOrderItems()
    {
        return $this->hasMany(PharmacyOrderItem::class);
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequiresPrescription($query)
    {
        return $query->where('requires_prescription', true);
    }

    public function scopeOverTheCounter($query)
    {
        return $query->where('requires_prescription', false);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('generic_name', 'LIKE', "%{$term}%")
                ->orWhere('code', 'LIKE', "%{$term}%")
                ->orWhere('manufacturer', 'LIKE', "%{$term}%");
        });
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ========== Accessors ==========
    public function getDisplayNameAttribute(): string
    {
        return $this->name . ($this->strength ? " ({$this->strength})" : '');
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

    // ========== Methods ==========
    public function generateCode(): string
    {
        $prefix = 'DRG';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}-{$random}";
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

    protected static function booted()
    {
        static::creating(function ($drug) {
            if (empty($drug->code)) {
                $drug->code = $drug->generateCode();
            }
        });
    }
}
