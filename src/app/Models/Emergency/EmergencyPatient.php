<?php
// app/Models/Emergency/EmergencyPatient.php

namespace App\Models\Emergency;

use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Admission;
use App\Models\Clinic;
use App\Models\Province;
use App\Models\City;
use Illuminate\Database\Eloquent\Model;

class EmergencyPatient extends Model
{
    protected $table = 'emergency_patients';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'admission_id',
        'clinic_id',
        'province_id',
        'city_id',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'request_latitude',
        'request_longitude',
        'request_address',
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
        'dispatched_at',
        'arrived_at',
        'completed_at',
        'ambulance_number',
        'ambulance_team',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'disposition_time' => 'datetime',
        'dispatched_at' => 'datetime',
        'arrived_at' => 'datetime',
        'completed_at' => 'datetime',
        'vital_signs' => 'array',
        'metadata' => 'array',
        'request_latitude' => 'float',
        'request_longitude' => 'float',
    ];

    // ============================================
    // Relationships
    // ============================================

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

    // ============================================
    // Accessors
    // ============================================

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

    public function getTriageLevelColorAttribute(): string
    {
        $colors = [
            'red' => 'danger',
            'yellow' => 'warning',
            'green' => 'success',
            'blue' => 'info',
        ];
        return $colors[$this->triage_level] ?? 'secondary';
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
            'dispatched' => 'آمبولانس اعزام شد',
            'arrived' => 'آمبولانس رسید',
            'completed' => 'تکمیل شد',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'waiting' => 'warning',
            'in_triage' => 'info',
            'in_exam' => 'primary',
            'in_treatment' => 'processing',
            'admitted' => 'blue',
            'discharged' => 'success',
            'transferred' => 'purple',
            'dispatched' => 'orange',
            'arrived' => 'cyan',
            'completed' => 'green',
        ];
        return $colors[$this->status] ?? 'secondary';
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

    public function getFullAddressAttribute(): string
    {
        $parts = [];
        if ($this->request_address) $parts[] = $this->request_address;
        if ($this->city) $parts[] = $this->city->name;
        if ($this->province) $parts[] = $this->province->name;
        return implode('، ', $parts);
    }

    // ============================================
    // Scopes
    // ============================================

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'in_triage', 'in_exam', 'in_treatment', 'dispatched', 'arrived']);
    }

    public function scopeByClinic($query, $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeByTriage($query, $level)
    {
        return $query->where('triage_level', $level);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('arrival_time', today());
    }

    // ============================================
    // Methods
    // ============================================

    public function dispatchAmbulance(string $ambulanceNumber, string $team = null): void
    {
        $this->update([
            'status' => 'dispatched',
            'ambulance_number' => $ambulanceNumber,
            'ambulance_team' => $team,
            'dispatched_at' => now(),
        ]);
    }

    public function markAsArrived(): void
    {
        $this->update([
            'status' => 'arrived',
            'arrived_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function setTriage(string $level, array $vitalSigns = null): void
    {
        $this->update([
            'triage_level' => $level,
            'vital_signs' => $vitalSigns,
            'status' => 'in_triage',
        ]);
    }

    public function startExam(): void
    {
        $this->update(['status' => 'in_exam']);
    }

    public function startTreatment(): void
    {
        $this->update(['status' => 'in_treatment']);
    }

    public function admit(): void
    {
        $this->update([
            'status' => 'admitted',
            'disposition' => 'admitted',
            'disposition_time' => now(),
        ]);
    }

    public function discharge(): void
    {
        $this->update([
            'status' => 'discharged',
            'disposition' => 'discharged',
            'disposition_time' => now(),
        ]);
    }

    public function transfer(string $toHospital): void
    {
        $this->update([
            'status' => 'transferred',
            'disposition' => 'transferred',
            'disposition_time' => now(),
            'metadata' => array_merge($this->metadata ?? [], ['transferred_to' => $toHospital]),
        ]);
    }
}
