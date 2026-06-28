<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EHRVisit extends Model
{
    protected $table = 'ehr_visits';

    protected $fillable = [
        'tenant_id',
        'ehr_record_id',
        'appointment_id',
        'doctor_id',
        'visit_type',
        'chief_complaint',
        'history_of_present_illness',
        'past_medical_history',
        'family_history',
        'social_history',
        'physical_exam',
        'assessment',
        'plan',
        'notes',
        'vital_signs',
        'visit_date',
    ];

    protected $casts = [
        'vital_signs' => 'array',
        'visit_date' => 'datetime',
    ];

    // ========== Relationships ==========
    public function ehrRecord()
    {
        return $this->belongsTo(EHRRecord::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    // ========== Accessors ==========
    public function getVisitTypeLabelAttribute(): string
    {
        $labels = [
            'initial' => 'ویزیت اولیه',
            'follow_up' => 'ویزیت پیگیری',
            'emergency' => 'ویزیت اورژانسی',
            'consultation' => 'مشاوره',
        ];
        return $labels[$this->visit_type] ?? $this->visit_type;
    }

    public function getVitalSignsDisplayAttribute(): array
    {
        $signs = $this->vital_signs ?? [];
        $display = [];
        $labels = [
            'blood_pressure' => 'فشار خون',
            'heart_rate' => 'ضربان قلب',
            'respiratory_rate' => 'تعداد تنفس',
            'temperature' => 'دما',
            'weight' => 'وزن',
            'height' => 'قد',
            'bmi' => 'BMI',
        ];

        foreach ($signs as $key => $value) {
            $display[$labels[$key] ?? $key] = $value;
        }

        return $display;
    }

    // ========== Scopes ==========
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('visit_date', $date);
    }

    // ========== Methods ==========
    public function getVitalSignsSummary(): string
    {
        $signs = $this->vital_signs ?? [];
        $parts = [];

        if (isset($signs['blood_pressure'])) {
            $parts[] = "فشار خون: {$signs['blood_pressure']}";
        }
        if (isset($signs['heart_rate'])) {
            $parts[] = "ضربان قلب: {$signs['heart_rate']}";
        }
        if (isset($signs['temperature'])) {
            $parts[] = "دما: {$signs['temperature']}°C";
        }

        return implode(' | ', $parts);
    }
}
