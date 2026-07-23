<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabTest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'code',
        'name',
        'short_name',
        'clinic_id',
        'province_id',
        'city_id',
        'description',
        'sample_type',
        'unit',
        'min_range',
        'max_range',
        'critical_low',
        'critical_high',
        'price',
        'turnaround_time',
        'is_active',
        'requires_fasting',
        'fasting_hours',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_fasting' => 'boolean',
        'price' => 'decimal:2',
        'min_range' => 'float',
        'max_range' => 'float',
        'critical_low' => 'float',
        'critical_high' => 'float',
        'fasting_hours' => 'integer',
        'turnaround_time' => 'integer',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function category()
    {
        return $this->belongsTo(LabCategory::class, 'category_id');
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function orderTests()
    {
        return $this->hasMany(LabOrderTest::class);
    }

    public function results()
    {
        return $this->hasMany(LabResult::class);
    }

    // ========== Accessors ==========
    public function getFullAddressAttribute(): string
    {
        $parts = [];
        if ($this->clinic) {
            $parts[] = $this->clinic->address;
            if ($this->city) $parts[] = $this->city->name;
            if ($this->province) $parts[] = $this->province->name;
        }
        return implode('، ', $parts);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->short_name ? "{$this->name} ({$this->short_name})" : $this->name;
    }

    public function getRangeDisplayAttribute(): string
    {
        if ($this->min_range !== null && $this->max_range !== null) {
            return "{$this->min_range} - {$this->max_range} {$this->unit}";
        }
        if ($this->min_range !== null) {
            return "≥ {$this->min_range} {$this->unit}";
        }
        if ($this->max_range !== null) {
            return "≤ {$this->max_range} {$this->unit}";
        }
        return '—';
    }

    public function getCriticalDisplayAttribute(): string
    {
        $parts = [];
        if ($this->critical_low !== null) {
            $parts[] = "کمتر از {$this->critical_low}";
        }
        if ($this->critical_high !== null) {
            $parts[] = "بیشتر از {$this->critical_high}";
        }
        return $parts ? implode(' یا ', $parts) : '—';
    }

    public function getPriceDisplayAttribute(): string
    {
        return number_format($this->price ?? 0) . ' تومان';
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('code', 'LIKE', "%{$term}%")
                ->orWhere('short_name', 'LIKE', "%{$term}%")
                ->orWhere('description', 'LIKE', "%{$term}%");
        });
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByClinic($query, $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    // ========== Methods ==========
    public function generateCode(): string
    {
        $prefix = 'LBT';
        $year = now()->format('y');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}-{$random}";
    }

    public function isAbnormal($value): bool
    {
        if ($this->min_range !== null && $value < $this->min_range) {
            return true;
        }
        if ($this->max_range !== null && $value > $this->max_range) {
            return true;
        }
        return false;
    }

    public function isCritical($value): bool
    {
        if ($this->critical_low !== null && $value < $this->critical_low) {
            return true;
        }
        if ($this->critical_high !== null && $value > $this->critical_high) {
            return true;
        }
        return false;
    }

    // ========== Boot ==========
    protected static function booted()
    {
        static::creating(function ($test) {
            if (empty($test->code)) {
                $test->code = $test->generateCode();
            }
        });
    }
}
