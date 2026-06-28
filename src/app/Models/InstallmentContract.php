<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallmentContract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'invoice_id',
        'contract_number',
        'total_amount',
        'down_payment',
        'installment_amount',
        'number_of_installments',
        'installments_paid',
        'interest_rate',
        'total_interest',
        'penalty_rate',
        'gateway',
        'gateway_reference',
        'status',
        'start_date',
        'end_date',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'total_interest' => 'decimal:2',
        'penalty_rate' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'metadata' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function installments()
    {
        return $this->hasMany(Installment::class);
    }

    public function paidInstallments()
    {
        return $this->hasMany(Installment::class)->where('status', 'paid');
    }

    public function pendingInstallments()
    {
        return $this->hasMany(Installment::class)->where('status', 'pending');
    }

    public function overdueInstallments()
    {
        return $this->hasMany(Installment::class)
            ->where('status', 'pending')
            ->where('due_date', '<', now());
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'در انتظار تایید',
            'active' => 'فعال',
            'completed' => 'تکمیل شده',
            'defaulted' => 'معوق',
            'cancelled' => 'لغو شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'active' => 'info',
            'completed' => 'success',
            'defaulted' => 'danger',
            'cancelled' => 'secondary',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getProgressAttribute(): int
    {
        if ($this->number_of_installments == 0) return 0;
        return round(($this->installments_paid / $this->number_of_installments) * 100);
    }

    public function getRemainingAmountAttribute(): float
    {
        $paid = $this->installments()->where('status', 'paid')->sum('amount');
        return $this->total_amount - $paid;
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->installments()->where('status', 'paid')->sum('amount');
    }

    public function getTotalPenaltyAttribute(): float
    {
        return $this->installments()->sum('penalty');
    }

    public function getNextDueAttribute(): ?Installment
    {
        return $this->installments()
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->first();
    }

    public function generateContractNumber(): string
    {
        $prefix = 'INST';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}-{$random}";
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isDefaulted(): bool
    {
        return $this->status === 'defaulted';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'active']) && $this->installments_paid == 0;
    }

    protected static function booted()
    {
        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $contract->contract_number = $contract->generateContractNumber();
            }
            if (empty($contract->start_date)) {
                $contract->start_date = now();
            }
        });
    }
}
