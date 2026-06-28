<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientVaccination extends Model
{
    protected $fillable = [
        'tenant_id',
        'patient_id',
        'vaccine_id',
        'doctor_id',
        'appointment_id',
        'dose_number',
        'administration_date',
        'next_due_date',
        'batch_number',
        'administration_site',
        'status',
        'reaction_notes',
        'is_valid',
        'metadata',
    ];

    protected $casts = [
        'administration_date' => 'date',
        'next_due_date' => 'date',
        'is_valid' => 'boolean',
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

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function reminder()
    {
        return $this->hasOne(VaccinationReminder::class);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'scheduled' => 'برنامه‌ریزی شده',
            'completed' => 'انجام شده',
            'missed' => 'از دست رفته',
            'cancelled' => 'لغو شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'scheduled' => 'warning',
            'completed' => 'success',
            'missed' => 'danger',
            'cancelled' => 'secondary',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->next_due_date) return false;
        return $this->next_due_date->isPast() && $this->status !== 'completed';
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByVaccine($query, $vaccineId)
    {
        return $query->where('vaccine_id', $vaccineId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'scheduled')
            ->whereDate('next_due_date', '<=', now());
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'is_valid' => true,
        ]);
    }

    public function markAsMissed(): void
    {
        $this->update([
            'status' => 'missed',
            'is_valid' => false,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'is_valid' => false,
        ]);
    }

    public function getVaccinationHistory(): array
    {
        $history = PatientVaccination::where('patient_id', $this->patient_id)
            ->where('vaccine_id', $this->vaccine_id)
            ->orderBy('dose_number')
            ->get();

        return [
            'vaccine' => $this->vaccine,
            'total_doses' => $history->count(),
            'completed_doses' => $history->where('status', 'completed')->count(),
            'history' => $history,
            'next_due' => $this->next_due_date,
            'is_completed' => $this->vaccine->doses_required <= $history->where('status', 'completed')->count(),
        ];
    }
}
