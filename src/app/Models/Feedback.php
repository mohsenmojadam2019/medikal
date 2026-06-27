<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'survey_response_id',
        'category',
        'rating',
        'comment',
        'suggestion',
        'admin_reply',
        'replied_at',
        'status',
        'is_anonymous',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'integer',
        'replied_at' => 'datetime',
        'is_anonymous' => 'boolean',
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

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function surveyResponse()
    {
        return $this->belongsTo(SurveyResponse::class);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'در انتظار بررسی',
            'read' => 'خوانده شده',
            'replied' => 'پاسخ داده شده',
            'resolved' => 'حل شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'read' => 'info',
            'replied' => 'primary',
            'resolved' => 'success',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getCategoryLabelAttribute(): string
    {
        $labels = [
            'general' => 'عمومی',
            'doctor' => 'پزشک',
            'facility' => 'امکانات',
            'staff' => 'کارکنان',
        ];
        return $labels[$this->category] ?? $this->category;
    }

    public function getRatingDisplayAttribute(): string
    {
        if (!$this->rating) return 'بدون امتیاز';
        return str_repeat('⭐', $this->rating);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeHighRating($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeLowRating($query, $maxRating = 2)
    {
        return $query->where('rating', '<=', $maxRating);
    }

    public function reply(string $reply): void
    {
        $this->update([
            'admin_reply' => $reply,
            'replied_at' => now(),
            'status' => 'replied',
        ]);
    }

    public function markAsRead(): void
    {
        $this->update(['status' => 'read']);
    }

    public function markAsResolved(): void
    {
        $this->update(['status' => 'resolved']);
    }
}
