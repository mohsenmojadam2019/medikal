<?php

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'patient_id',
        'transaction_id',
        'reference_code',
        'amount',
        'gateway',
        'status',
        'authority',
        'message',
        'raw_data',
        'payment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'raw_data' => 'array',
        'status' => PaymentStatusEnum::class,
    ];

    // ========== Relationships ==========
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? 'نامشخص';
    }

    public function getGatewayLabelAttribute(): string
    {
        $labels = [
            'zarinpal' => 'زرین‌پال',
            'asanpardakht' => 'آسان پرداخت',
            'paypal' => 'پی‌پال',
            'stripe' => 'استرایپ',
            'local' => 'محلی (تست)',
        ];
        return $labels[$this->gateway] ?? $this->gateway;
    }

    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === PaymentStatusEnum::SUCCESS;
    }

    // ========== Scopes ==========
    public function scopeSuccess($query)
    {
        return $query->where('status', PaymentStatusEnum::SUCCESS);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatusEnum::PENDING);
    }

    public function scopeByGateway($query, $gateway)
    {
        return $query->where('gateway', $gateway);
    }
}
