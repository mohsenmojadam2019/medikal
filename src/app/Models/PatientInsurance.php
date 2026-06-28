<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientInsurance extends Model
{
    protected $fillable = [
        'tenant_id',
        'patient_id',
        'insurance_id',
        'policy_number',
        'card_number',
        'expiry_date',
        'is_active',
        'is_primary',
        'coverage_percentage',
        'metadata',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'coverage_percentage' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }

    public function appointmentInsurances()
    {
        return $this->hasMany(AppointmentInsurance::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'فعال' : 'غیرفعال';
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expiry_date) return false;
        return $this->expiry_date->isPast();
    }

    public function getCoverageDisplayAttribute(): string
    {
        $coverage = $this->coverage_percentage ?? $this->insurance->coverage_percentage;
        return $coverage . '%';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function getCoveragePercentage(): float
    {
        return $this->coverage_percentage ?? $this->insurance->coverage_percentage;
    }

    public function calculateCoverage(float $amount): array
    {
        $coveragePercent = $this->getCoveragePercentage();
        $insuranceShare = $amount * ($coveragePercent / 100);
        $patientShare = $amount - $insuranceShare;

        if ($this->insurance->max_coverage_per_visit && $insuranceShare > $this->insurance->max_coverage_per_visit) {
            $insuranceShare = $this->insurance->max_coverage_per_visit;
            $patientShare = $amount - $insuranceShare;
        }

        return [
            'total_amount' => $amount,
            'insurance_share' => round($insuranceShare, 2),
            'patient_share' => round($patientShare, 2),
            'coverage_percentage' => $coveragePercent,
        ];
    }

    protected static function booted()
    {
        static::creating(function ($patientInsurance) {
            if (empty($patientInsurance->policy_number)) {
                $patientInsurance->policy_number = 'POL-' . strtoupper(\Illuminate\Support\Str::random(10));
            }
        });
    }
}
