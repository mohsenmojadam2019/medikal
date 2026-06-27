<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneAppointment extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'receptionist_id',
        'appointment_id',
        'caller_name',
        'caller_phone',
        'caller_relation',
        'appointment_date',
        'appointment_time',
        'reason',
        'notes',
        'status',
        'confirmed_at',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime',
        'confirmed_at' => 'datetime',
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

    public function receptionist()
    {
        return $this->belongsTo(User::class, 'receptionist_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'در انتظار تایید',
            'confirmed' => 'تایید شده',
            'completed' => 'انجام شده',
            'cancelled' => 'لغو شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'confirmed' => 'success',
            'completed' => 'info',
            'cancelled' => 'danger',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    // ========== Scopes ==========
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('appointment_date', $date);
    }

    // ========== Methods ==========
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }
}
