<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'clinic_id',
        'enable_installments',
        'max_installments',
        'min_installment_amount',
        'default_interest_rate',
        'default_penalty_rate',
        'grace_days',
        'available_gateways',
        'require_down_payment',
        'metadata',
    ];

    protected $casts = [
        'enable_installments' => 'boolean',
        'max_installments' => 'integer',
        'min_installment_amount' => 'integer',
        'default_interest_rate' => 'decimal:2',
        'default_penalty_rate' => 'decimal:2',
        'grace_days' => 'integer',
        'available_gateways' => 'array',
        'require_down_payment' => 'boolean',
        'metadata' => 'array',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function getIsEnabledAttribute(): bool
    {
        return $this->enable_installments;
    }

    public function getAvailableGatewaysListAttribute(): array
    {
        return $this->available_gateways ?? ['walleta', 'ezpay', 'toplend'];
    }

    public function enable(): void
    {
        $this->update(['enable_installments' => true]);
    }

    public function disable(): void
    {
        $this->update(['enable_installments' => false]);
    }

    public function toggle(): void
    {
        $this->update(['enable_installments' => !$this->enable_installments]);
    }
}
