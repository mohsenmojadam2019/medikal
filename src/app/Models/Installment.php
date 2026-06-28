<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    protected $fillable = [
        'tenant_id',
        'contract_id',
        'installment_number',
        'amount',
        'penalty',
        'paid_amount',
        'due_date',
        'paid_date',
        'status',
        'payment_reference',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'penalty' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'metadata' => 'array',
    ];

    public function contract()
    {
        return $this->belongsTo(InstallmentContract::class);
    }

    public function payments()
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'در انتظار',
            'paid' => 'پرداخت شده',
            'overdue' => 'معوق',
            'waived' => 'بخشیده شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'paid' => 'success',
            'overdue' => 'danger',
            'waived' => 'info',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) return 0;
        return now()->diffInDays($this->due_date);
    }

    public function getTotalPayableAttribute(): float
    {
        return $this->amount + $this->penalty;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now());
    }

    public function scopeDueSoon($query, $days = 7)
    {
        return $query->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function markAsPaid(array $data = []): void
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now(),
            'paid_amount' => $data['amount'] ?? $this->amount,
            'penalty' => $data['penalty'] ?? $this->penalty,
            'payment_reference' => $data['reference'] ?? null,
            'metadata' => array_merge($this->metadata ?? [], $data['metadata'] ?? []),
        ]);
    }

    public function markAsOverdue(): void
    {
        $this->update(['status' => 'overdue']);
    }

    public function waive(): void
    {
        $this->update(['status' => 'waived']);
    }

    public function calculatePenalty(): float
    {
        if (!$this->is_overdue) return 0;

        $contract = $this->contract;
        $daysOverdue = $this->days_overdue;
        $monthsOverdue = ceil($daysOverdue / 30);

        $penaltyRate = $contract->penalty_rate ?? 2;
        $penalty = ($this->amount * $penaltyRate / 100) * $monthsOverdue;

        return round(min($penalty, $this->amount * 0.5), 2);
    }
}
