<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'logo',
        'email',
        'phone',
        'address',
        'subscription_status',
        'subscription_expires_at',
        'trial_ends_at',
        'max_doctors',
        'max_patients',
        'max_appointments_per_day',
        'max_prescriptions_per_month',
        'features',
        'settings',
        'is_active',
        'is_verified',
        'created_by',
    ];

    protected $casts = [
        'subscription_expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'max_doctors' => 'integer',
        'max_patients' => 'integer',
        'max_appointments_per_day' => 'integer',
        'max_prescriptions_per_month' => 'integer',
        'features' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
    ];

    // ========== Relationships ==========
    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot('role', 'is_active', 'is_primary', 'joined_at')
            ->withTimestamps();
    }

    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->latest();
    }

    public function settings()
    {
        return $this->hasMany(TenantSetting::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'trial' => 'آزمایشی',
            'active' => 'فعال',
            'inactive' => 'غیرفعال',
            'expired' => 'منقضی',
            'cancelled' => 'لغو شده',
        ];
        return $labels[$this->subscription_status] ?? $this->subscription_status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'trial' => 'warning',
            'active' => 'success',
            'inactive' => 'secondary',
            'expired' => 'danger',
            'cancelled' => 'danger',
        ];
        return $colors[$this->subscription_status] ?? 'secondary';
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->subscription_expires_at) return false;
        return $this->subscription_expires_at->isPast();
    }

    public function getIsTrialAttribute(): bool
    {
        return $this->subscription_status === 'trial';
    }

    public function getIsActiveSubscriptionAttribute(): bool
    {
        return $this->subscription_status === 'active' && !$this->is_expired;
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSubscribed($query)
    {
        return $query->where('subscription_status', 'active')
            ->where('subscription_expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('subscription_status', 'expired')
                ->orWhere('subscription_expires_at', '<', now());
        });
    }

    public function scopeTrial($query)
    {
        return $query->where('subscription_status', 'trial');
    }

    // ========== Methods ==========
    public function generateSlug(): string
    {
        $slug = \Illuminate\Support\Str::slug($this->name);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function canAddDoctor(): bool
    {
        if ($this->max_doctors === -1) return true; // نامحدود
        return $this->doctors()->count() < $this->max_doctors;
    }

    public function canAddPatient(): bool
    {
        if ($this->max_patients === -1) return true; // نامحدود
        return $this->patients()->count() < $this->max_patients;
    }

    public function canAddAppointment(): bool
    {
        if ($this->max_appointments_per_day === -1) return true;
        $todayCount = $this->appointments()->whereDate('date', today())->count();
        return $todayCount < $this->max_appointments_per_day;
    }

    public function getFeature(string $key, $default = null)
    {
        return $this->features[$key] ?? $default;
    }

    public function hasFeature(string $key): bool
    {
        return isset($this->features[$key]) && $this->features[$key] === true;
    }

    public function getSetting(string $key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public function setSetting(string $key, $value, string $type = 'string'): void
    {
        $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }

    protected static function booted()
    {
        static::creating(function ($tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = $tenant->generateSlug();
            }
            if (empty($tenant->trial_ends_at)) {
                $tenant->trial_ends_at = now()->addDays(30);
            }
            if (empty($tenant->subscription_status)) {
                $tenant->subscription_status = 'trial';
            }
        });
    }
}
