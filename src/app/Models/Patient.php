<?php
// app/Models/Patient.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'doctor_id',
        'national_code',
        'full_name',
        'phone',
        'address',
        'province_id',        // ✅ اضافه شد
        'city_id',            // ✅ اضافه شد
        'latitude',           // ✅ اضافه شد
        'longitude',          // ✅ اضافه شد
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
        'latitude' => 'float',
        'longitude' => 'float',
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

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
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

    /**
     * دریافت آدرس اصلی بیمار
     */
    public function primaryAddress(): HasOne
    {
        return $this->hasOne(Address::class, 'patient_id', 'id')
            ->where('is_primary', true)
            ->where('is_active', true);
    }

    /**
     * دریافت تمام آدرس‌های بیمار
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'patient_id', 'id');
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

    public function getFullAddressAttribute(): string
    {
        $parts = [];
        if ($this->address) $parts[] = $this->address;
        if ($this->city) $parts[] = $this->city->name;
        if ($this->province) $parts[] = $this->province->name;
        return implode('، ', $parts);
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

    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }

    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
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

    public function scopeNearby($query, $lat, $lng, $radius = 10)
    {
        return $query->selectRaw("
            *,
            (6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            )) AS distance
        ", [$lat, $lng, $lat])
            ->having('distance', '<', $radius)
            ->orderBy('distance', 'asc');
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

    public function toggleStatus(): void
    {
        $this->update(['is_active' => !$this->is_active]);
    }

    public function verify(): void
    {
        $this->update(['verified_at' => now()]);
    }

    public function unverify(): void
    {
        $this->update(['verified_at' => null]);
    }

    public function assignDoctor(int $doctorId): void
    {
        $this->update(['doctor_id' => $doctorId]);
    }
}
