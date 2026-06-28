<?php

namespace App\Models;

use App\Enums\LabResultStatusEnum;
use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    protected $fillable = [
        'tenant_id',
        'lab_order_id',
        'lab_order_test_id',
        'lab_test_id',
        'value',
        'range_low',
        'range_high',
        'unit',
        'status',
        'is_abnormal',
        'is_critical',
        'comment',
        'interpretation',
        'verified_at',
        'verified_by',
        'metadata',
    ];

    protected $casts = [
        'value' => 'float',
        'range_low' => 'float',
        'range_high' => 'float',
        'is_abnormal' => 'boolean',
        'is_critical' => 'boolean',
        'status' => LabResultStatusEnum::class,
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function labOrderTest()
    {
        return $this->belongsTo(LabOrderTest::class);
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function files()
    {
        return $this->hasMany(LabResultFile::class);
    }

    // ========== Scopes ==========
    public function scopeAbnormal($query)
    {
        return $query->where('is_abnormal', true);
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', LabResultStatusEnum::COMPLETED);
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? 'در انتظار';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->color() ?? 'secondary';
    }

    public function getRangeDisplayAttribute(): string
    {
        if ($this->range_low !== null && $this->range_high !== null) {
            return "{$this->range_low} - {$this->range_high} {$this->unit}";
        }
        if ($this->range_low !== null) {
            return "≥ {$this->range_low} {$this->unit}";
        }
        if ($this->range_high !== null) {
            return "≤ {$this->range_high} {$this->unit}";
        }
        return '—';
    }

    public function getValueDisplayAttribute(): string
    {
        if ($this->value !== null) {
            return "{$this->value} {$this->unit}";
        }
        return '—';
    }

    public function getStatusIconAttribute(): string
    {
        if ($this->is_critical) {
            return '🔴';
        }
        if ($this->is_abnormal) {
            return '🟡';
        }
        return '🟢';
    }

    // ========== Methods ==========
    public function verify(): void
    {
        $this->update([
            'status' => LabResultStatusEnum::COMPLETED,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);
    }

    public function markAsAbnormal(): void
    {
        $this->update([
            'is_abnormal' => true,
            'status' => LabResultStatusEnum::ABNORMAL,
        ]);
    }

    public function markAsCritical(): void
    {
        $this->update([
            'is_critical' => true,
            'is_abnormal' => true,
            'status' => LabResultStatusEnum::CRITICAL,
        ]);
    }
}
