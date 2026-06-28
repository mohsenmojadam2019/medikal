<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentPayment extends Model
{
    protected $fillable = [
        'tenant_id',
        'installment_id',
        'payment_id',
        'amount',
        'penalty',
        'total_paid',
        'payment_method',
        'reference_code',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'penalty' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function installment()
    {
        return $this->belongsTo(Installment::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
