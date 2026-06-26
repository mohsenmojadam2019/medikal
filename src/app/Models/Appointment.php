<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Shetabit\Multipay\Invoice;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'code',
        'date',
        'start_time',
        'end_time',
        'duration',
        'status',
        'type',
        'fee',
        'discount',
        'final_price',
        'payment_status',
        'payment_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    // ========== Status Constants ==========
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';

    // ========== Relationships ==========
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    // ========== Scopes ==========
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_ARRIVED,
            self::STATUS_IN_PROGRESS
        ])->whereDate('date', '>=', today());
    }

    public function scopePast($query)
    {
        return $query->whereIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_NO_SHOW
        ])->orWhereDate('date', '<', today());
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_PENDING => 'در انتظار تایید',
            self::STATUS_CONFIRMED => 'تایید شده',
            self::STATUS_ARRIVED => 'حاضر در مطب',
            self::STATUS_IN_PROGRESS => 'در حال ویزیت',
            self::STATUS_COMPLETED => 'انجام شده',
            self::STATUS_CANCELLED => 'لغو شده',
            self::STATUS_NO_SHOW => 'حاضر نشده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_ARRIVED => 'primary',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_NO_SHOW => 'secondary',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        $labels = [
            self::PAYMENT_PENDING => 'در انتظار پرداخت',
            self::PAYMENT_PAID => 'پرداخت شده',
            self::PAYMENT_FAILED => 'ناموفق',
            self::PAYMENT_REFUNDED => 'عودت داده شده',
        ];
        return $labels[$this->payment_status] ?? $this->payment_status;
    }

    public function getIsUpcomingAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_ARRIVED,
            self::STATUS_IN_PROGRESS
        ]) && $this->date >= today();
    }

    public function getIsPastAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_NO_SHOW
        ]) || $this->date < today();
    }

    public function getIsCancellableAttribute(): bool
    {
        $now = now();
        $appointmentDateTime = Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->start_time->format('H:i:s'));

        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED
        ]) && $appointmentDateTime->diffInHours($now) >= 2;
    }

    public function getCanRescheduleAttribute(): bool
    {
        $now = now();
        $appointmentDateTime = Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->start_time->format('H:i:s'));

        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED
        ]) && $appointmentDateTime->diffInHours($now) >= 4;
    }

    // ========== Methods ==========
    public function generateCode(): string
    {
        $prefix = 'APP';
        $year = now()->format('y');
        $month = now()->format('m');
        $day = now()->format('d');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}{$month}{$day}-{$random}";
    }

    public function canCancel(): bool
    {
        return $this->is_cancellable;
    }

    public function canReschedule(): bool
    {
        return $this->can_reschedule;
    }

    // ========== Boot Methods ==========
    protected static function booted()
    {
        static::creating(function ($appointment) {
            if (empty($appointment->code)) {
                $appointment->code = $appointment->generateCode();
            }
        });
    }
}
