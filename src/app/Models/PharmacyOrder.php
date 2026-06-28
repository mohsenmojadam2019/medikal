<?php

namespace App\Models;

use App\Enums\PharmacyOrderStatusEnum;
use App\Enums\PharmacyOrderPaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmacyOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'order_number', 'patient_id', 'pharmacy_id', 'prescription_id',
        'status', 'payment_status', 'subtotal', 'insurance_share',
        'patient_share', 'delivery_fee', 'total_amount', 'payment_link',
        'paid_at', 'available_items', 'unavailable_items', 'metadata',
        'notes', 'confirmed_at', 'ready_at', 'delivered_at', 'cancelled_at'
    ];

    protected $casts = [
        'status' => PharmacyOrderStatusEnum::class,
        'payment_status' => PharmacyOrderPaymentStatusEnum::class,
        'available_items' => 'array',
        'unavailable_items' => 'array',
        'metadata' => 'array',
        'subtotal' => 'decimal:2',
        'insurance_share' => 'decimal:2',
        'patient_share' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    // ✅ اصلاح رابطه - استفاده از order_id
    public function items()
    {
        return $this->hasMany(PharmacyOrderItem::class, 'order_id');
    }

    public function notifications()
    {
        return $this->hasMany(PharmacyNotification::class);
    }

    public function generateOrderNumber(): string
    {
        $prefix = 'PH';
        $year = now()->format('y');
        $month = now()->format('m');
        $day = now()->format('d');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}{$day}-{$random}";
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? 'نامشخص';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->color() ?? 'secondary';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return $this->payment_status?->label() ?? 'نامشخص';
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === PharmacyOrderPaymentStatusEnum::PAID;
    }

    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = $order->generateOrderNumber();
            }
        });
    }
}
