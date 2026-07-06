<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'doctor_id',
        'national_code',
        'full_name',
        'phone',
        'address',
        'insurance_type',
        'insurance_number',
        'is_active',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

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

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function pharmacyOrders()
    {
        return $this->hasMany(PharmacyOrder::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // ========== Accessors ==========
    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? $this->full_name ?? 'کاربر';
    }

    public function getInsuranceTypeLabelAttribute(): string
    {
        $types = [
            'tamin_ejtemaei' => 'تامین اجتماعی',
            'tamin_tekamili' => 'بیمه تکمیلی',
            'asal' => 'بیمه آسایش',
            'iran' => 'بیمه ایران',
            'dana' => 'بیمه دانا',
            'saman' => 'بیمه سامان',
            'other' => 'سایر',
        ];
        return $types[$this->insurance_type] ?? $this->insurance_type ?? 'ندارد';
    }

    public function getIsProfileCompleteAttribute(): bool
    {
        return !empty($this->user?->name) &&
            !empty($this->user?->mobile) &&
            !empty($this->national_code) &&
            !empty($this->address);
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

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('full_name', 'LIKE', "%{$term}%")
                ->orWhere('national_code', 'LIKE', "%{$term}%")
                ->orWhere('phone', 'LIKE', "%{$term}%")
                ->orWhereHas('user', function ($u) use ($term) {
                    $u->where('name', 'LIKE', "%{$term}%")
                        ->orWhere('mobile', 'LIKE', "%{$term}%");
                });
        });
    }

    // ========== Methods ==========
    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    public function markAsActive(): void
    {
        $this->update(['is_active' => true]);
    }

    public function markAsInactive(): void
    {
        $this->update(['is_active' => false]);
    }
}
