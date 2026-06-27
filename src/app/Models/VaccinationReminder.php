<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VaccinationReminder extends Model
{
    protected $fillable = [
        'patient_id',
        'vaccine_id',
        'patient_vaccination_id',
        'reminder_date',
        'type',
        'status',
        'sent_at',
        'message',
        'metadata',
    ];

    protected $casts = [
        'reminder_date' => 'date',
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function vaccine()
    {
        return $this->belongsTo(Vaccine::class);
    }

    public function patientVaccination()
    {
        return $this->belongsTo(PatientVaccination::class);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'در انتظار',
            'sent' => 'ارسال شده',
            'completed' => 'انجام شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'next_dose' => 'یادآوری دوز بعدی',
            'follow_up' => 'یادآوری پیگیری',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
            ->whereDate('reminder_date', '<=', now());
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }
}
