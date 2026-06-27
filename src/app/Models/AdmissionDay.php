<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionDay extends Model
{
    protected $fillable = [
        'admission_id',
        'day_number',
        'date',
        'temperature',
        'heart_rate',
        'respiratory_rate',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'oxygen_saturation',
        'pain_score',
        'weight',
        'height',
        'bmi',
        'consciousness_level',
        'notes',
        'nurse_id',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'temperature' => 'float',
        'heart_rate' => 'integer',
        'respiratory_rate' => 'integer',
        'blood_pressure_systolic' => 'integer',
        'blood_pressure_diastolic' => 'integer',
        'oxygen_saturation' => 'integer',
        'pain_score' => 'integer',
        'weight' => 'float',
        'height' => 'float',
        'bmi' => 'float',
        'metadata' => 'array',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function getConsciousnessLevelLabelAttribute(): string
    {
        $labels = [
            'alert' => 'هوشیار',
            'drowsy' => 'خواب‌آلود',
            'stupor' => 'نیمه‌هوشیار',
            'coma' => 'کما',
        ];
        return $labels[$this->consciousness_level] ?? $this->consciousness_level;
    }

    public function getBloodPressureDisplayAttribute(): string
    {
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            return "{$this->blood_pressure_systolic}/{$this->blood_pressure_diastolic}";
        }
        return '—';
    }

    public function getPainScoreDisplayAttribute(): string
    {
        if ($this->pain_score === null) return '—';
        return $this->pain_score . '/10';
    }

    public function getVitalSignsSummaryAttribute(): string
    {
        $parts = [];
        if ($this->temperature) $parts[] = "دما: {$this->temperature}°C";
        if ($this->heart_rate) $parts[] = "ضربان: {$this->heart_rate} bpm";
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            $parts[] = "فشار: {$this->blood_pressure_display}";
        }
        if ($this->oxygen_saturation) $parts[] = "اکسیژن: {$this->oxygen_saturation}%";
        return implode(' | ', $parts);
    }
}
