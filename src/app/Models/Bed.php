<?php

namespace App\Models;

use App\Enums\BedStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bed extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'ward_id',
        'bed_number',
        'code',
        'status',
        'is_private',
        'price_per_day',
        'description',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'status' => BedStatusEnum::class,
        'is_private' => 'boolean',
        'price_per_day' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function currentAdmission()
    {
        return $this->hasOne(Admission::class)
            ->where('status', AdmissionStatusEnum::IN_PROGRESS)
            ->latest();
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    // ========== Scopes ==========
    public function scopeAvailable($query)
    {
        return $query->where('status', BedStatusEnum::AVAILABLE);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', BedStatusEnum::OCCUPIED);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? 'نامشخص';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->color() ?? 'secondary';
    }

    public function getPriceDisplayAttribute(): string
    {
        return number_format($this->price_per_day ?? 0) . ' تومان';
    }

    public function getIsOccupiedAttribute(): bool
    {
        return $this->status === BedStatusEnum::OCCUPIED;
    }

    public function getDisplayNameAttribute(): string
    {
        return "تخت {$this->bed_number} - بخش {$this->ward->name}";
    }

    // ========== Methods ==========
    public function generateCode(): string
    {
        $prefix = 'BED';
        $year = now()->format('y');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}-{$random}";
    }

    public function occupy(): void
    {
        $this->update(['status' => BedStatusEnum::OCCUPIED]);
        if ($this->ward) {
            $this->ward->updateOccupancy();
        }
    }

    public function free(): void
    {
        $this->update(['status' => BedStatusEnum::AVAILABLE]);
        if ($this->ward) {
            $this->ward->updateOccupancy();
        }
    }

    public function reserve(): void
    {
        $this->update(['status' => BedStatusEnum::RESERVED]);
    }

    public function maintenance(): void
    {
        $this->update(['status' => BedStatusEnum::MAINTENANCE]);
    }

    public function clean(): void
    {
        $this->update(['status' => BedStatusEnum::CLEANING]);
    }

    protected static function booted()
    {
        static::creating(function ($bed) {
            if (empty($bed->code)) {
                $bed->code = $bed->generateCode();
            }
            if (empty($bed->price_per_day) && $bed->ward) {
                $bed->price_per_day = $bed->ward->daily_rate;
            }
        });
    }
}
