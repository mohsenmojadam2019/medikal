<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_id',
        'patient_id',
        'appointment_id',
        'doctor_id',
        'answers',
        'score',
        'feedback',
        'status',
        'completed_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'integer',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'completed' => 'تکمیل شده',
            'partial' => 'ناقص',
            'expired' => 'منقضی',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getScoreDisplayAttribute(): string
    {
        return $this->score ? $this->score . ' از ۵' : 'نامشخص';
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeBySurvey($query, $surveyId)
    {
        return $query->where('survey_id', $surveyId);
    }

    public function scopeHighScore($query, $minScore = 4)
    {
        return $query->where('score', '>=', $minScore);
    }

    public function scopeLowScore($query, $maxScore = 2)
    {
        return $query->where('score', '<=', $maxScore);
    }
}
