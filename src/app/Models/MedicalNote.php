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
        'type',
        'priority',
        'is_private',
        'is_shared',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_shared' => 'boolean',
        'tags' => 'array',
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

    public function getExcerptAttribute(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->content), 100);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }
}
