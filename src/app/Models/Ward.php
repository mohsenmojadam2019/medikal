<?php

namespace App\Models;

use App\Enums\WardTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ward extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'floor',
        'capacity',
        'current_occupancy',
        'description',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'type' => WardTypeEnum::class,
        'capacity' => 'integer',
        'current_occupancy' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function beds()
    {
        return $this->hasMany(Bed::class);
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, WardTypeEnum $type)
    {
        return $query->where('type', $type);
    }

    // ========== Accessors ==========
    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? 'نامشخص';
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type?->color() ?? 'secondary';
    }

    public function getDailyRateAttribute(): float
    {
        return $this->type?->dailyRate() ?? 0;
    }

    public function getAvailableBedsAttribute(): int
    {
        return $this->beds()->where('status', BedStatusEnum::AVAILABLE)->count();
    }

    public function getOccupancyRateAttribute(): float
    {
        if ($this->capacity == 0) return 0;
        return round(($this->current_occupancy / $this->capacity) * 100, 1);
    }

    public function getStatusAttribute(): string
    {
        if ($this->available_beds > 0) return 'available';
        if ($this->current_occupancy == $this->capacity) return 'full';
        return 'maintenance';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available' => '🟢 در دسترس',
            'full' => '🔴 تکمیل',
            'maintenance' => '🟡 در حال تعمیر',
            default => 'نامشخص',
        };
    }

    // ========== Methods ==========
    public function generateCode(): string
    {
        $prefix = 'WRD';
        $year = now()->format('y');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}-{$random}";
    }

    public function updateOccupancy(): void
    {
        $this->update([
            'current_occupancy' => $this->beds()
                ->where('status', BedStatusEnum::OCCUPIED)
                ->count(),
        ]);
    }

    public function hasAvailableBed(): bool
    {
        return $this->available_beds > 0;
    }

    public function getAvailableBed(): ?Bed
    {
        return $this->beds()
            ->where('status', BedStatusEnum::AVAILABLE)
            ->first();
    }

    protected static function booted()
    {
        static::creating(function ($ward) {
            if (empty($ward->code)) {
                $ward->code = $ward->generateCode();
            }
        });
    }
}
