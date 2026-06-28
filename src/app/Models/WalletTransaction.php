<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'tenant_id',
        'wallet_id',
        'user_id',
        'appointment_id',
        'invoice_id',
        'transaction_id',
        'type',
        'status',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference',
        'metadata',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    // ========== Relationships ==========
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // ========== Accessors ==========
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'deposit' => 'شارژ کیف پول',
            'withdraw' => 'برداشت از کیف پول',
            'payment' => 'پرداخت',
            'refund' => 'بازگشت وجه',
            'bonus' => 'پاداش',
            'transfer' => 'انتقال وجه',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'در انتظار',
            'completed' => 'انجام شده',
            'failed' => 'ناموفق',
            'cancelled' => 'لغو شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getAmountDisplayAttribute(): string
    {
        $sign = $this->amount > 0 ? '+' : '';
        return $sign . number_format($this->amount) . ' تومان';
    }

    public function getIsIncomeAttribute(): bool
    {
        return $this->amount > 0;
    }

    public function getIsExpenseAttribute(): bool
    {
        return $this->amount < 0;
    }

    // ========== Scopes ==========
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDate($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
