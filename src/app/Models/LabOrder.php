<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'order_number',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'lab_technician_id',
        'status',
        'priority',
        'sample_type',
        'sample_collected_at',
        'sample_received_at',
        'result_ready_at',
        'notes',
        'clinical_history',
        'metadata',
        'cancelled_at',
        'cancelled_reason',
        'rejected_at',
        'rejected_reason',
    ];

    protected $casts = [
        'sample_collected_at' => 'datetime',
        'sample_received_at' => 'datetime',
        'result_ready_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rejected_at' => 'datetime',
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

    public function labTechnician()
    {
        return $this->belongsTo(User::class, 'lab_technician_id');
    }

    public function orderTests()
    {
        return $this->hasMany(LabOrderTest::class);
    }

    public function results()
    {
        return $this->hasMany(LabResult::class);
    }

    public function invoice()
    {
        return $this->morphOne(Invoice::class, 'invoicable');
    }

    // ========== Scopes ==========
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            'pending', 'waiting_payment', 'paid', 'scheduled',
            'sample_collected', 'processing', 'partial'
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'در انتظار',
            'waiting_payment' => 'در انتظار پرداخت',
            'paid' => 'پرداخت شده',
            'scheduled' => 'نوبت‌دهی شده',
            'sample_collected' => 'نمونه گرفته شده',
            'processing' => 'در حال پردازش',
            'partial' => 'تکمیل بخشی',
            'completed' => 'تکمیل شده',
            'cancelled' => 'لغو شده',
            'rejected' => 'رد شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'waiting_payment' => 'gold',
            'paid' => 'cyan',
            'scheduled' => 'blue',
            'sample_collected' => 'purple',
            'processing' => 'processing',
            'partial' => 'orange',
            'completed' => 'success',
            'cancelled' => 'error',
            'rejected' => 'error',
        ];
        return $colors[$this->status] ?? 'default';
    }

    public function getPriorityLabelAttribute(): string
    {
        $labels = [
            'routine' => 'معمولی',
            'urgent' => 'فوری',
            'stat' => 'اورژانسی',
        ];
        return $labels[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute(): string
    {
        $colors = [
            'routine' => 'blue',
            'urgent' => 'orange',
            'stat' => 'red',
        ];
        return $colors[$this->priority] ?? 'default';
    }

    public function getTotalPriceAttribute(): float
    {
        return $this->orderTests()->sum('total_price') ?? 0;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsActiveAttribute(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled', 'rejected']);
    }

    // ========== Methods ==========
    public function generateOrderNumber(): string
    {
        $prefix = 'LBR';
        $year = now()->format('y');
        $month = now()->format('m');
        $day = now()->format('d');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}{$day}-{$random}";
    }

    public function changeStatus(string $status, ?string $reason = null): void
    {
        $this->update([
            'status' => $status,
            'cancelled_at' => $status === 'cancelled' ? now() : null,
            'cancelled_reason' => $status === 'cancelled' ? $reason : null,
            'rejected_at' => $status === 'rejected' ? now() : null,
            'rejected_reason' => $status === 'rejected' ? $reason : null,
        ]);
    }

    // ========== Boot ==========
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = $order->generateOrderNumber();
            }
        });
    }
}
