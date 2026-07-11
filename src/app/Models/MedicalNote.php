<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'title',
        'content',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'diagnoses',
        'prescriptions',
        'lab_requests',
        'imaging_requests',
        'referrals',
        'type',
        'priority',
        'is_private',
        'is_shared',
        'note_status',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'diagnoses' => 'array',
        'prescriptions' => 'array',
        'lab_requests' => 'array',
        'imaging_requests' => 'array',
        'referrals' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'is_private' => 'boolean',
        'is_shared' => 'boolean',
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

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // ========== Accessors ==========
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'general' => 'عمومی',
            'prescription' => 'نسخه',
            'diagnosis' => 'تشخیص',
            'follow_up' => 'پیگیری',
            'referral' => 'ارجاع',
            'emergency' => 'اورژانس',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function getPriorityLabelAttribute(): string
    {
        $labels = [
            'low' => 'کم',
            'normal' => 'معمولی',
            'high' => 'بالا',
            'urgent' => 'فوری',
        ];
        return $labels[$this->priority] ?? $this->priority;
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'draft' => 'پیش‌نویس',
            'final' => 'نهایی',
            'shared' => 'به اشتراک گذاشته شده',
        ];
        return $labels[$this->note_status] ?? $this->note_status;
    }

    // ========== Scopes ==========
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByAppointment($query, $appointmentId)
    {
        return $query->where('appointment_id', $appointmentId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeFinal($query)
    {
        return $query->where('note_status', 'final');
    }

    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    // ========== Methods ==========
    public function addDiagnosis(array $diagnosis): void
    {
        $diagnoses = $this->diagnoses ?? [];
        $diagnoses[] = $diagnosis;
        $this->update(['diagnoses' => $diagnoses]);
    }

    public function addPrescription(array $prescription): void
    {
        $prescriptions = $this->prescriptions ?? [];
        $prescriptions[] = $prescription;
        $this->update(['prescriptions' => $prescriptions]);
    }

    public function addLabRequest(array $labRequest): void
    {
        $labRequests = $this->lab_requests ?? [];
        $labRequests[] = $labRequest;
        $this->update(['lab_requests' => $labRequests]);
    }

    public function addImagingRequest(array $imagingRequest): void
    {
        $imagingRequests = $this->imaging_requests ?? [];
        $imagingRequests[] = $imagingRequest;
        $this->update(['imaging_requests' => $imagingRequests]);
    }

    public function addReferral(array $referral): void
    {
        $referrals = $this->referrals ?? [];
        $referrals[] = $referral;
        $this->update(['referrals' => $referrals]);
    }

    public function markAsFinal(): void
    {
        $this->update(['note_status' => 'final']);
    }

    public function markAsShared(): void
    {
        $this->update([
            'is_shared' => true,
            'note_status' => 'shared',
        ]);
    }
}
