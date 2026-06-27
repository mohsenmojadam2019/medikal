<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EHRRecord extends Model
{
    protected $table = 'ehr_records';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'record_number',
        'title',
        'description',
        'diagnosis',
        'treatment_plan',
        'notes',
        'status',
        'is_emergency',
        'is_confidential',
        'recorded_at',
        'metadata',
    ];

    protected $casts = [
        'is_emergency' => 'boolean',
        'is_confidential' => 'boolean',
        'recorded_at' => 'datetime',
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

    public function visits()
    {
        return $this->hasMany(EHRVisit::class);
    }

    public function documents()
    {
        return $this->hasMany(MedicalDocument::class);
    }

    public function alerts()
    {
        return $this->hasMany(MedicalAlert::class);
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'active' => 'فعال',
            'completed' => 'تکمیل شده',
            'archived' => 'بایگانی شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'active' => 'success',
            'completed' => 'info',
            'archived' => 'secondary',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    // ========== Methods ==========
    public function generateRecordNumber(): string
    {
        $prefix = 'EHR';
        $year = now()->format('y');
        $month = now()->format('m');
        $day = now()->format('d');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}{$day}-{$random}";
    }

    public function getFullHistory(): array
    {
        return [
            'record' => $this->load(['patient', 'doctor']),
            'visits' => $this->visits()->with(['doctor'])->orderBy('visit_date', 'desc')->get(),
            'documents' => $this->documents()->orderBy('created_at', 'desc')->get(),
            'alerts' => $this->alerts()->where('is_active', true)->get(),
            'appointments' => $this->patient->appointments()->with(['doctor'])->orderBy('date', 'desc')->limit(10)->get(),
            'prescriptions' => $this->patient->prescriptions()->with(['doctor'])->orderBy('created_at', 'desc')->limit(10)->get(),
        ];
    }

    protected static function booted()
    {
        static::creating(function ($record) {
            if (empty($record->record_number)) {
                $record->record_number = $record->generateRecordNumber();
            }
            if (empty($record->recorded_at)) {
                $record->recorded_at = now();
            }
        });
    }
}
