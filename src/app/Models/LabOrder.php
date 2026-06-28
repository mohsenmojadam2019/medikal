<?php

namespace App\Models;

use App\Enums\LabOrderStatusEnum;
use App\Enums\LabPriorityEnum;
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
        'status' => LabOrderStatusEnum::class,
        'priority' => LabPriorityEnum::class,
        'sample_type' => LabSampleTypeEnum::class,
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

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // ========== Scopes ==========
    public function scopePending($query)
    {
        return $query->where('status', LabOrderStatusEnum::PENDING);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            LabOrderStatusEnum::PENDING,
            LabOrderStatusEnum::WAITING_PAYMENT,
            LabOrderStatusEnum::PAID,
            LabOrderStatusEnum::SCHEDULED,
            LabOrderStatusEnum::SAMPLE_COLLECTED,
            LabOrderStatusEnum::PROCESSING,
            LabOrderStatusEnum::PARTIAL,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', LabOrderStatusEnum::COMPLETED);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByDate($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? 'نامشخص';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->color() ?? 'secondary';
    }

    public function getPriorityLabelAttribute(): string
    {
        return $this->priority?->label() ?? 'معمولی';
    }

    public function getPriorityColorAttribute(): string
    {
        return $this->priority?->color() ?? 'secondary';
    }

    public function getSampleTypeLabelAttribute(): string
    {
        return $this->sample_type?->label() ?? 'نامشخص';
    }

    public function getTotalPriceAttribute(): float
    {
        return $this->orderTests()->sum('total_price') ?? 0;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === LabOrderStatusEnum::COMPLETED;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status?->isActive() ?? false;
    }

    public function getIsFinalAttribute(): bool
    {
        return $this->status?->isFinal() ?? false;
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

    public function changeStatus(LabOrderStatusEnum $status, ?string $reason = null): void
    {
        $this->update([
            'status' => $status,
            'cancelled_at' => $status === LabOrderStatusEnum::CANCELLED ? now() : $this->cancelled_at,
            'cancelled_reason' => $status === LabOrderStatusEnum::CANCELLED ? $reason : $this->cancelled_reason,
            'rejected_at' => $status === LabOrderStatusEnum::REJECTED ? now() : $this->rejected_at,
            'rejected_reason' => $status === LabOrderStatusEnum::REJECTED ? $reason : $this->rejected_reason,
        ]);
    }

    public function markAsSampleCollected(): void
    {
        $this->update([
            'status' => LabOrderStatusEnum::SAMPLE_COLLECTED,
            'sample_collected_at' => now(),
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => LabOrderStatusEnum::PROCESSING,
            'sample_received_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => LabOrderStatusEnum::COMPLETED,
            'result_ready_at' => now(),
        ]);
    }

    public function cancel(string $reason): void
    {
        $this->changeStatus(LabOrderStatusEnum::CANCELLED, $reason);
    }

    public function reject(string $reason): void
    {
        $this->changeStatus(LabOrderStatusEnum::REJECTED, $reason);
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
