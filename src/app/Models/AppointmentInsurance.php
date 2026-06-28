<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentInsurance extends Model
{
    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'patient_insurance_id',
        'total_amount',
        'insurance_share',
        'patient_share',
        'deductible',
        'status',
        'claim_number',
        'submitted_at',
        'approved_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'insurance_share' => 'decimal:2',
        'patient_share' => 'decimal:2',
        'deductible' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patientInsurance()
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'در انتظار تایید',
            'approved' => 'تایید شده',
            'rejected' => 'رد شده',
            'paid' => 'پرداخت شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'paid' => 'info',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByAppointment($query, $appointmentId)
    {
        return $query->where('appointment_id', $appointmentId);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
    }

    public function generateClaimNumber(): string
    {
        $prefix = 'CLM';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}-{$random}";
    }

    protected static function booted()
    {
        static::creating(function ($appointmentInsurance) {
            if (empty($appointmentInsurance->claim_number)) {
                $appointmentInsurance->claim_number = $appointmentInsurance->generateClaimNumber();
            }
        });
    }
}
