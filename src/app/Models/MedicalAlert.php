<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalAlert extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'type',
        'title',
        'description',
        'severity',
        'is_active',
        'is_read',
        'read_at',
        'resolved_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'resolved_at' => 'datetime',
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

    // ========== Accessors ==========
    public function getSeverityLabelAttribute(): string
    {
        $labels = [
            'low' => 'کم',
            'medium' => 'متوسط',
            'high' => 'بالا',
            'critical' => 'بحرانی',
        ];
        return $labels[$this->severity] ?? $this->severity;
    }

    public function getSeverityColorAttribute(): string
    {
        $colors = [
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'danger',
        ];
        return $colors[$this->severity] ?? 'secondary';
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'allergy' => 'حساسیت',
            'drug_interaction' => 'تداخل دارویی',
            'chronic_disease' => 'بیماری مزمن',
            'critical_result' => 'نتیجه بحرانی',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    // ========== Methods ==========
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsResolved(): void
    {
        $this->update([
            'is_active' => false,
            'resolved_at' => now(),
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'resolved_at' => null,
        ]);
    }
}
