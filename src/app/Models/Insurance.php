<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Insurance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'coverage_percentage',
        'max_coverage_per_year',
        'max_coverage_per_visit',
        'logo',
        'is_active',
        'contract_start_date',
        'contract_end_date',
        'services',
        'metadata',
    ];

    protected $casts = [
        'coverage_percentage' => 'decimal:2',
        'max_coverage_per_year' => 'decimal:2',
        'max_coverage_per_visit' => 'decimal:2',
        'is_active' => 'boolean',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'services' => 'array',
        'metadata' => 'array',
    ];

    public function patientInsurances()
    {
        return $this->hasMany(PatientInsurance::class);
    }

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'patient_insurances');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'فعال' : 'غیرفعال';
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->contract_end_date) return false;
        return $this->contract_end_date->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('contract_end_date')
                    ->orWhere('contract_end_date', '>=', now());
            });
    }

    public function calculateCoverage(float $amount): array
    {
        $insuranceShare = $amount * ($this->coverage_percentage / 100);
        $patientShare = $amount - $insuranceShare;

        if ($this->max_coverage_per_visit && $insuranceShare > $this->max_coverage_per_visit) {
            $insuranceShare = $this->max_coverage_per_visit;
            $patientShare = $amount - $insuranceShare;
        }

        return [
            'total_amount' => $amount,
            'insurance_share' => round($insuranceShare, 2),
            'patient_share' => round($patientShare, 2),
            'coverage_percentage' => $this->coverage_percentage,
        ];
    }

    protected static function booted()
    {
        static::creating(function ($insurance) {
            if (empty($insurance->code)) {
                $insurance->code = 'INS-' . strtoupper(\Illuminate\Support\Str::random(8));
            }
        });
    }
}
