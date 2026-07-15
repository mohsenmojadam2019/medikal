<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\PharmacyOrderStatusEnum;
use App\Enums\PharmacyOrderPaymentStatusEnum;

class PharmacyOrder extends Model
{
    use SoftDeletes;

    protected $table = 'pharmacy_orders';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'pharmacy_id',
        'prescription_id',
        'order_number',
        'status',
        'payment_status',
        'subtotal',
        'total_amount',
        'delivery_fee',
        'tax',
        'insurance_share',
        'patient_share',
        'payment_gateway',
        'payment_authority',
        'payment_link',
        'paid_at',
        'confirmed_at',
        'ready_at',
        'delivered_at',
        'cancelled_at',
        'metadata',
        'notes',

        // ✅ اضافه کردن فیلدهای اطلاعات تحویل
        'recipient_name',
        'recipient_phone',
        'delivery_address',
        'delivery_notes',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'tax' => 'decimal:2',
        'insurance_share' => 'decimal:2',
        'patient_share' => 'decimal:2',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
        'available_items' => 'array',
        'unavailable_items' => 'array',
    ];

    protected $appends = [
        'status_label',
        'status_color',
        'payment_status_label',
        'is_paid',
    ];

    // ============================================
    // Relationships
    // ============================================

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function items()
    {
        return $this->hasMany(PharmacyOrderItem::class);
    }

    public function notifications()
    {
        return $this->hasMany(PharmacyNotification::class);
    }

    // ============================================
    // Accessors
    // ============================================

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pending' => 'در انتظار',
            'payment_pending' => 'در انتظار پرداخت',
            'paid' => 'پرداخت شده',
            'processing' => 'در حال پردازش',
            'preparing' => 'در حال آماده‌سازی',
            'ready' => 'آماده',
            'shipped' => 'ارسال شده',
            'delivered' => 'تحویل شده',
            'cancelled' => 'لغو شده',
            'failed' => 'ناموفق',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'pending', 'payment_pending' => 'warning',
            'paid', 'processing', 'preparing' => 'info',
            'ready', 'shipped', 'delivered' => 'success',
            'cancelled', 'failed' => 'danger',
            default => 'default',
        };
    }

    public function getPaymentStatusLabelAttribute()
    {
        return match ($this->payment_status) {
            'pending' => 'در انتظار پرداخت',
            'paid' => 'پرداخت شده',
            'failed' => 'ناموفق',
            'refunded' => 'عودت داده شده',
            default => $this->payment_status,
        };
    }

    public function getIsPaidAttribute()
    {
        return $this->payment_status === 'paid';
    }

    // ============================================
    // Scopes
    // ============================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaymentPending($query)
    {
        return $query->where('status', 'payment_pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
