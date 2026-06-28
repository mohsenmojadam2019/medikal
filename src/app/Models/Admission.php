<?php

namespace App\Models;

use App\Enums\AdmissionStatusEnum;
use App\Enums\BedStatusEnum;
use App\Enums\WardTypeEnum;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admission extends Model
{
    use SoftDeletes, HasTenant;

    protected $fillable = [
        'tenant_id',
        'admission_number',
        'patient_id',
        'doctor_id',
        'ward_id',
        'bed_id',
        'status',
        'admission_date',
        'admission_time',
        'diagnosis',
        'chief_complaint',
        'history_of_present_illness',
        'past_medical_history',
        'allergies',
        'medications',
        'emergency_contact',
        'emergency_phone',
        'notes',
        'metadata',
        'discharged_at',
    ];

    protected $casts = [
        'status' => AdmissionStatusEnum::class,
        'admission_date' => 'date',
        'admission_time' => 'datetime',
        'discharged_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function services()
    {
        return $this->hasMany(AdmissionService::class);
    }

    public function drugs()
    {
        return $this->hasMany(AdmissionDrug::class);
    }

    public function days()
    {
        return $this->hasMany(AdmissionDay::class);
    }

    public function discharge()
    {
        return $this->hasOne(Discharge::class);
    }

    public function invoice()
    {
        return $this->morphOne(Invoice::class, 'invoicable');
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            AdmissionStatusEnum::ADMITTED,
            AdmissionStatusEnum::IN_PROGRESS,
        ]);
    }

    public function scopeDischarged($query)
    {
        return $query->where('status', AdmissionStatusEnum::DISCHARGED);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByWard($query, $wardId)
    {
        return $query->where('ward_id', $wardId);
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

    public function getDurationAttribute(): int
    {
        $start = $this->admission_date;
        $end = $this->discharged_at ?? now();
        return $start->diffInDays($end) + 1;
    }

    public function getTotalCostAttribute(): float
    {
        $bedCost = $this->duration * $this->bed?->price_per_day ?? 0;
        $servicesCost = $this->services()->sum('price');
        $drugsCost = $this->drugs()->sum('total_price');
        return $bedCost + $servicesCost + $drugsCost;
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, [
            AdmissionStatusEnum::ADMITTED,
            AdmissionStatusEnum::IN_PROGRESS,
        ]);
    }

    // ========== Methods ==========
    public function generateAdmissionNumber(): string
    {
        $prefix = 'ADM';
        $year = now()->format('y');
        $month = now()->format('m');
        $day = now()->format('d');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}{$day}-{$random}";
    }

    public function admit(): void
    {
        $this->update(['status' => AdmissionStatusEnum::ADMITTED]);
        if ($this->bed) {
            $this->bed->occupy();
        }
    }

    public function startProgress(): void
    {
        $this->update(['status' => AdmissionStatusEnum::IN_PROGRESS]);
    }

    public function markAsDischarged(): void
    {
        $this->update([
            'status' => AdmissionStatusEnum::DISCHARGED,
            'discharged_at' => now(),
        ]);
        if ($this->bed) {
            $this->bed->free();
        }
    }

    public function cancel(): void
    {
        $this->update(['status' => AdmissionStatusEnum::CANCELLED]);
        if ($this->bed && $this->bed->status === BedStatusEnum::OCCUPIED) {
            $this->bed->free();
        }
    }

    protected static function booted()
    {
        static::creating(function ($admission) {
            if (empty($admission->admission_number)) {
                $admission->admission_number = $admission->generateAdmissionNumber();
            }
            if (empty($admission->admission_date)) {
                $admission->admission_date = now()->toDateString();
            }
            if (empty($admission->admission_time)) {
                $admission->admission_time = now();
            }
        });
    }
}
