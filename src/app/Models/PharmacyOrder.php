<?php
// app/Models/PharmacyOrder.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'recipient_name',
        'recipient_phone',
        'delivery_address',
        'delivery_notes',
        // ✅ فیلدهای جدید نسخه پزشکی
        'prescription_file',
        'prescription_status',
        'prescription_reject_reason',
        'prescription_approved_at',
        'prescription_rejected_at',
        'prescription_approved_by',
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
        'prescription_approved_at' => 'datetime',
        'prescription_rejected_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'status_color',
        'payment_status_label',
        'is_paid',
        'prescription_status_label', // ✅ اضافه شد
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

    public function prescriptionApprovedBy()
    {
        return $this->belongsTo(User::class, 'prescription_approved_by');
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

    // ✅ اضافه شد
    public function getPrescriptionStatusLabelAttribute()
    {
        return match ($this->prescription_status) {
            'none' => 'نیاز به نسخه ندارد',
            'pending' => 'در انتظار بررسی نسخه',
            'approved' => 'نسخه تایید شد ✅',
            'rejected' => 'نسخه رد شد ❌',
            default => $this->prescription_status,
        };
    }

    public function getPrescriptionFileUrlAttribute()
    {
        if ($this->prescription_file) {
            return asset('storage/' . $this->prescription_file);
        }
        return null;
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

    // ✅ اسکوپ جدید
    public function scopePrescriptionPending($query)
    {
        return $query->where('prescription_status', 'pending');
    }

    public function scopePrescriptionApproved($query)
    {
        return $query->where('prescription_status', 'approved');
    }
}
