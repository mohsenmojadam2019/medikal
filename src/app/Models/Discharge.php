<?php

namespace App\Models;

use App\Enums\DischargeStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Discharge extends Model
{
    protected $fillable = [
        'tenant_id',
        'admission_id',
        'discharge_number',
        'discharge_date',
        'final_diagnosis',
        'summary',
        'medications_at_discharge',
        'follow_up_instructions',
        'follow_up_date',
        'doctor_id',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'discharge_date' => 'datetime',
        'follow_up_date' => 'datetime',
        'status' => DischargeStatusEnum::class,
        'metadata' => 'array',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? 'نامشخص';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->color() ?? 'secondary';
    }

    public function generateDischargeNumber(): string
    {
        $prefix = 'DSC';
        $year = now()->format('y');
        $month = now()->format('m');
        $day = now()->format('d');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}{$day}-{$random}";
    }

    public function approve(): void
    {
        $this->update(['status' => DischargeStatusEnum::APPROVED]);
    }

    public function complete(): void
    {
        $this->update(['status' => DischargeStatusEnum::COMPLETED]);
        $this->admission->discharge();
    }

    public function cancel(): void
    {
        $this->update(['status' => DischargeStatusEnum::CANCELLED]);
    }

    protected static function booted()
    {
        static::creating(function ($discharge) {
            if (empty($discharge->discharge_number)) {
                $discharge->discharge_number = $discharge->generateDischargeNumber();
            }
            if (empty($discharge->discharge_date)) {
                $discharge->discharge_date = now();
            }
            if (empty($discharge->status)) {
                $discharge->status = DischargeStatusEnum::PENDING;
            }
        });
    }
}
