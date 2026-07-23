<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'clinic_id',
        'province_id',
        'city_id',
        'specialty_id',
        'license_number',
        'clinic_name',
        'clinic_address',
        'latitude',
        'longitude',
        'clinic_phone',
        'clinic_email',
        'profile_image',
        'bio',
        'biography',
        'education',
        'certificates',
        'social_links',
        'working_hours',
        'experience_years',
        'consultation_fee',
        'appointment_fee_type',
        'appointment_fee_amount',
        'is_fee_editable_by_admin',
        'visit_duration',
        'is_available',
        'is_verified',
        'is_active',
        'rating',
        'total_reviews',
        'metadata',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'social_links' => 'array',
        'metadata' => 'array',
        'experience_years' => 'integer',
        'consultation_fee' => 'decimal:2',
        'appointment_fee_amount' => 'decimal:2',
        'is_available' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'is_fee_editable_by_admin' => 'boolean',
        'rating' => 'decimal:2',
    ];

    // ========== Relationships ==========
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function primaryAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('is_primary', true);
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function medicalNotes()
    {
        return $this->hasMany(MedicalNote::class);
    }

    public function medicalImages()
    {
        return $this->hasMany(MedicalImage::class);
    }

    // ========== Accessors ==========
    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? $this->name ?? 'پزشک';
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [];
        if ($this->clinic_address) $parts[] = $this->clinic_address;
        if ($this->city) $parts[] = $this->city->name;
        if ($this->province) $parts[] = $this->province->name;
        return implode('، ', $parts);
    }

    public function getAppointmentFeeLabelAttribute(): string
    {
        if ($this->appointment_fee_type === 'free') {
            return 'رایگان';
        }
        return number_format($this->appointment_fee_amount ?? 0) . ' تومان';
    }

    public function getAppointmentFeeValueAttribute(): float
    {
        if ($this->appointment_fee_type === 'free') {
            return 0;
        }
        return (float) ($this->appointment_fee_amount ?? $this->consultation_fee ?? 0);
    }

    public function isFreeAppointment(): bool
    {
        return $this->appointment_fee_type === 'free';
    }

    public function getFeeForAppointment(): float
    {
        if ($this->appointment_fee_type === 'free') {
            return 0;
        }
        return (float) ($this->appointment_fee_amount ?? $this->consultation_fee ?? 0);
    }

    // ========== Scopes ==========
    public function scopeFreeAppointments($query)
    {
        return $query->where('appointment_fee_type', 'free');
    }

    public function scopePaidAppointments($query)
    {
        return $query->where('appointment_fee_type', 'paid');
    }

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('clinic_name', 'LIKE', "%{$term}%")
                ->orWhere('license_number', 'LIKE', "%{$term}%")
                ->orWhereHas('user', function ($q2) use ($term) {
                    $q2->where('name', 'LIKE', "%{$term}%")
                        ->orWhere('mobile', 'LIKE', "%{$term}%");
                });
        });
    }

    public function scopeBySpecialty($query, $specialtyId)
    {
        return $query->where('specialty_id', $specialtyId);
    }

    public function scopeByClinic($query, $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    // ========== Methods ==========
    public function toggleAvailability(): void
    {
        $this->update(['is_available' => !$this->is_available]);
    }

    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    public function unverify(): void
    {
        $this->update(['is_verified' => false]);
    }
}
