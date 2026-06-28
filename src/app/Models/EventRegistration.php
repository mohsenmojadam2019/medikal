<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    protected $fillable = [
        'tenant_id',
        'event_id',
        'patient_id',
        'registration_code',
        'status',
        'notes',
        'metadata',
        'confirmed_at',
        'attended_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'attended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function getStatusLabelAttribute(): string    {
        $labels = [
            'pending' => 'در انتظار تایید',
            'confirmed' => 'تایید شده',
            'attended' => 'حضور یافته',
            'cancelled' => 'لغو شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'confirmed' => 'success',
            'attended' => 'primary',
            'cancelled' => 'danger',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeByEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function generateRegistrationCode(): string
    {
        $prefix = 'REG';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}-{$random}";
    }

    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        $this->event->increment('current_participants');
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
        $this->event->decrement('current_participants');
    }

    public function markAsAttended(): void
    {
        $this->update([
            'status' => 'attended',
            'attended_at' => now(),
        ]);
    }

    protected static function booted()
    {
        static::creating(function ($registration) {
            if (empty($registration->registration_code)) {
                $registration->registration_code = $registration->generateRegistrationCode();
            }
        });
    }
}
