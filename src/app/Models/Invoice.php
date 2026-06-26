<?php

namespace App\Models;

use App\Enums\InvoiceStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'invoice_number',
        'amount',
        'tax',
        'discount',
        'total_amount',
        'description',
        'status',
        'due_date',
        'paid_at',
        'items',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'items' => 'array',
        'metadata' => 'array',
        'status' => InvoiceStatusEnum::class,
    ];

    // ========== Relationships ==========
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
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

    public function getIsPaidAttribute(): bool
    {
        return $this->status === InvoiceStatusEnum::PAID;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === InvoiceStatusEnum::OVERDUE;
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->where('status', 'success')->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->total_amount - $this->total_paid;
    }

    public function getItemsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // ========== Scopes ==========
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByStatus($query, InvoiceStatusEnum $status)
    {
        return $query->where('status', $status);
    }

    public function scopeIssued($query)
    {
        return $query->where('status', InvoiceStatusEnum::ISSUED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatusEnum::PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', InvoiceStatusEnum::OVERDUE)
            ->orWhere(function ($q) {
                $q->where('status', InvoiceStatusEnum::ISSUED)
                    ->whereDate('due_date', '<', now());
            });
    }

    // ========== Methods ==========
    public function generateNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}{$month}-{$random}";
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => InvoiceStatusEnum::PAID,
            'paid_at' => now(),
        ]);
    }

    public function markAsIssued(): void
    {
        $this->update(['status' => InvoiceStatusEnum::ISSUED]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => InvoiceStatusEnum::CANCELLED]);
    }

    public function markAsOverdue(): void
    {
        $this->update(['status' => InvoiceStatusEnum::OVERDUE]);
    }

    public function addItem(array $item): void
    {
        $items = $this->items ?? [];
        $items[] = $item;
        $this->update(['items' => $items]);
    }

    public function calculateTotal(): float
    {
        $subtotal = $this->amount ?? 0;
        $tax = $subtotal * ($this->tax / 100);
        $discount = $this->discount ?? 0;
        return $subtotal + $tax - $discount;
    }

    // ========== Boot Methods ==========
    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = $invoice->generateNumber();
            }
            if (empty($invoice->status)) {
                $invoice->status = InvoiceStatusEnum::DRAFT;
            }
            if (!empty($invoice->items)) {
                $invoice->total_amount = $invoice->calculateTotal();
            }
        });
    }
}
