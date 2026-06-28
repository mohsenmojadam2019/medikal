<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'created_by',
        'status',
        'start_date',
        'end_date',
        'cancelled_at',
        'amount_paid',
        'payment_gateway',
        'payment_reference',
        'invoice_number',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'amount_paid' => 'decimal:2',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'active' => 'فعال',
            'expired' => 'منقضی',
            'cancelled' => 'لغو شده',
            'trial' => 'آزمایشی',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'active' => 'success',
            'expired' => 'danger',
            'cancelled' => 'secondary',
            'trial' => 'warning',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && $this->end_date && $this->end_date->isFuture();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status === 'expired' || ($this->end_date && $this->end_date->isPast());
    }

    public function getRemainingDaysAttribute(): int
    {
        if (!$this->end_date || $this->is_expired) return 0;
        return now()->diffInDays($this->end_date, false);
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('end_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere('end_date', '<', now());
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ========== Methods ==========
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-SUB';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}-{$random}";
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'start_date' => $this->start_date ?? now(),
            'end_date' => $this->end_date ?? now()->addMonth(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function expire(): void
    {
        $this->update([
            'status' => 'expired',
            'end_date' => now(),
        ]);
    }

    public function renew(int $months = 1): void
    {
        $newEndDate = $this->end_date 
            ? $this->end_date->addMonths($months) 
            : now()->addMonths($months);

        $this->update([
            'status' => 'active',
            'end_date' => $newEndDate,
        ]);
    }

    protected static function booted()
    {
        static::creating(function ($subscription) {
            if (empty($subscription->invoice_number)) {
                $subscription->invoice_number = $subscription->generateInvoiceNumber();
            }
        });
    }
}
