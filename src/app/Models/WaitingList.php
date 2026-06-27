<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitingList extends Model
{
    protected $table = 'waiting_list';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'queue_number',
        'status',
        'type',
        'entered_at',
        'called_at',
        'started_at',
        'completed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'called_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'waiting' => 'در انتظار',
            'in_progress' => 'در حال ویزیت',
            'completed' => 'انجام شده',
            'cancelled' => 'لغو شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'waiting' => 'warning',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'walk_in' => 'حضوری',
            'phone' => 'تلفنی',
            'online' => 'آنلاین',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function getEstimatedWaitTimeAttribute(): int
    {
        // تخمین زمان انتظار بر اساس تعداد افراد در صف
        $waitingCount = static::where('doctor_id', $this->doctor_id)
            ->where('status', 'waiting')
            ->where('id', '<', $this->id)
            ->count();

        $avgVisitDuration = 30; // دقیقه
        return $waitingCount * $avgVisitDuration;
    }

    // ========== Scopes ==========
    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('entered_at', today());
    }

    // ========== Methods ==========
    public function markAsCalled(): void
    {
        $this->update([
            'status' => 'in_progress',
            'called_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function generateQueueNumber(): int
    {
        $last = static::where('doctor_id', $this->doctor_id)
            ->whereDate('entered_at', today())
            ->max('queue_number');

        return ($last ?? 0) + 1;
    }
}
