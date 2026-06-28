<?php

namespace App\Models\Emergency;

use Illuminate\Database\Eloquent\Model;

class EmergencyPatient extends Model
{
    protected $table = 'emergency_patients';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'admission_id',
        'triage_level',
        'arrival_time',
        'chief_complaint',
        'history_of_present_illness',
        'vital_signs',
        'allergies',
        'medications',
        'past_medical_history',
        'status',
        'disposition',
        'disposition_time',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'disposition_time' => 'datetime',
        'vital_signs' => 'array',
        'metadata' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function getTriageLevelLabelAttribute(): string
    {
        $labels = [
            'red' => '🔴 بحرانی (فوری)',
            'yellow' => '🟡 فوری',
            'green' => '🟢 معمولی',
            'blue' => '🔵 غیرفوری',
        ];
        return $labels[$this->triage_level] ?? $this->triage_level;
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'waiting' => 'در انتظار',
            'in_triage' => 'در حال تریاز',
            'in_exam' => 'در حال معاینه',
            'in_treatment' => 'در حال درمان',
            'admitted' => 'بستری',
            'discharged' => 'ترخیص',
            'transferred' => 'منتقل شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getDispositionLabelAttribute(): string
    {
        $labels = [
            'discharged' => 'ترخیص',
            'admitted' => 'بستری',
            'transferred' => 'منتقل به بیمارستان دیگر',
            'died' => 'فوت',
            'left_against_advice' => 'ترخیص با رضایت شخصی',
        ];
        return $labels[$this->disposition] ?? $this->disposition;
    }
}
