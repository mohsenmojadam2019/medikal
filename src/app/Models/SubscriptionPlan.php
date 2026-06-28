<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'price_monthly',
        'price_yearly',
        'is_free',
        'max_doctors',
        'max_patients',
        'max_appointments_per_day',
        'max_prescriptions_per_month',
        'features',
        'permissions',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_free' => 'boolean',
        'max_doctors' => 'integer',
        'max_patients' => 'integer',
        'max_appointments_per_day' => 'integer',
        'max_prescriptions_per_month' => 'integer',
        'features' => 'array',
        'permissions' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // ========== Relationships ==========
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    // ========== Accessors ==========
    public function getPriceMonthlyFormattedAttribute(): string
    {
        return number_format($this->price_monthly) . ' تومان';
    }

    public function getPriceYearlyFormattedAttribute(): string
    {
        return number_format($this->price_yearly) . ' تومان';
    }

    public function getIsFreeAttribute(): bool
    {
        return $this->price_monthly == 0;
    }

    public function getFeaturesListAttribute(): array
    {
        return $this->features ?? [];
    }

    public function getFeature(string $key, $default = null)
    {
        return $this->features[$key] ?? $default;
    }

    public function hasFeature(string $key): bool
    {
        return isset($this->features[$key]) && $this->features[$key] === true;
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function scopePaid($query)
    {
        return $query->where('is_free', false);
    }

    // ========== Methods ==========
    public function generateSlug(): string
    {
        return \Illuminate\Support\Str::slug($this->name);
    }

    protected static function booted()
    {
        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = $plan->generateSlug();
            }
        });
    }
}
