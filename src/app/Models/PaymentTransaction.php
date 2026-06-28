<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'user_id',
        'transaction_id',
        'gateway',
        'amount',
        'currency',
        'status',
        'reference_code',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'در انتظار',
            'success' => 'موفق',
            'failed' => 'ناموفق',
            'refunded' => 'عودت داده شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'success' => 'success',
            'failed' => 'danger',
            'refunded' => 'info',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getGatewayLabelAttribute(): string
    {
        $labels = [
            'zarinpal' => 'زرین‌پال',
            'asanpardakht' => 'آسان‌پرداخت',
            'local' => 'تست',
        ];
        return $labels[$this->gateway] ?? $this->gateway;
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
