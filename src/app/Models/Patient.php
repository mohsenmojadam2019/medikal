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
        'national_code',
        'full_name',
        'phone',
        'is_active',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

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

    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? $this->full_name ?? 'کاربر';
    }
}
