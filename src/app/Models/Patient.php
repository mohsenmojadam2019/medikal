<?php

namespace App\Models;

use App\Traits\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes, HasSeo;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'doctor_id',
        'national_code',
        'phone',
        'emergency_contact',
        'blood_type',
        'is_active',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];
    public function seo()
    {
        return $this->morphOne(Seo::class, 'seoable');
    }

    public function getSeoTitleAttribute()
    {
        return $this->full_name ?? null;
    }

    public function getSeoDescriptionAttribute()
    {
        return null;
    }

    public function getSeoKeywordsAttribute()
    {
        return null;
    }
    // ========== Relationships ==========
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function primaryAddress()
    {
        return $this->morphOne(Address::class, 'addressable')
            ->where('is_primary', true);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // ========== Accessors ==========
    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? 'بیمار';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->user?->name ?? $this->user?->mobile ?? 'بیمار ناشناس';
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['phone'] ?? $this->user?->mobile;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function getFullAddressAttribute(): ?string
    {
        $address = $this->primaryAddress;
        return $address ? $address->full_address : null;
    }

    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->verified_at);
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_at');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('national_code', 'LIKE', "%{$term}%")
                ->orWhere('phone', 'LIKE', "%{$term}%")
                ->orWhereHas('user', function ($user) use ($term) {
                    $user->where('name', 'LIKE', "%{$term}%")
                        ->orWhere('mobile', 'LIKE', "%{$term}%")
                        ->orWhere('email', 'LIKE', "%{$term}%");
                });
        });
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    // ========== Methods ==========
    public function verify(): void
    {
        $this->update(['verified_at' => now()]);
    }

    public function unverify(): void
    {
        $this->update(['verified_at' => null]);
    }

    public function toggleStatus(): void
    {
        $this->update(['is_active' => !$this->is_active]);
    }

    public function assignDoctor(int $doctorId): void
    {
        $this->update(['doctor_id' => $doctorId]);
    }

    public function getMedicalHistory(): array
    {
        return [
            'appointments' => $this->appointments()
                ->with(['doctor.user', 'doctor.specialty'])
                ->orderBy('date', 'desc')
                ->limit(20)
                ->get(),
            'prescriptions' => $this->prescriptions()
                ->with(['doctor.user'])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get(),
            'total_appointments' => $this->appointments()->count(),
            'total_prescriptions' => $this->prescriptions()->count(),
            'last_visit' => $this->appointments()
                ->where('status', Appointment::STATUS_COMPLETED)
                ->orderBy('date', 'desc')
                ->first(),
            'upcoming_appointments' => $this->appointments()
                ->upcoming()
                ->with(['doctor.user', 'doctor.specialty'])
                ->get(),
        ];
    }

    public function getStatistics(): array
    {
        return [
            'total_appointments' => $this->appointments()->count(),
            'completed_appointments' => $this->appointments()
                ->byStatus(Appointment::STATUS_COMPLETED)
                ->count(),
            'cancelled_appointments' => $this->appointments()
                ->byStatus(Appointment::STATUS_CANCELLED)
                ->count(),
            'no_show_appointments' => $this->appointments()
                ->byStatus(Appointment::STATUS_NO_SHOW)
                ->count(),
            'active_prescriptions' => $this->prescriptions()
                ->active()
                ->count(),
            'total_invoices' => $this->invoices()->count(),
            'total_paid' => $this->invoices()->where('status', 'paid')->sum('total_amount'),
            'total_unpaid' => $this->invoices()->where('status', 'issued')->sum('total_amount'),
        ];
    }

    protected static function booted()
    {
        static::creating(function ($patient) {
            if (empty($patient->verified_at)) {
                if ($patient->user && $patient->user->mobile_verified_at) {
                    $patient->verified_at = now();
                }
            }
        });
    }
}
