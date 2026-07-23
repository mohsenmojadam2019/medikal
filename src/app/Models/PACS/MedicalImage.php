<?php
// app/Models/PACS/MedicalImage.php

namespace App\Models\PACS;

use App\Models\Admission;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Clinic;
use App\Models\Province;
use App\Models\City;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalImage extends Model
{
    use SoftDeletes;

    protected $table = 'medical_images';

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'doctor_id',
        'clinic_id',
        'province_id',
        'city_id',
        'admission_id',
        'appointment_id',
        'image_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'study_uid',
        'series_uid',
        'instance_uid',
        'body_part',
        'modality',
        'description',
        'study_date',
        'report',
        'is_confidential',
        'uploaded_by',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'study_date' => 'datetime',
        'is_confidential' => 'boolean',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    // ========== Relationships ==========

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
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

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // ========== Accessors ==========

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getFileSizeDisplayAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getImageTypeLabelAttribute(): string
    {
        $labels = [
            'xray' => 'رادیوگرافی',
            'ct' => 'سی‌تی اسکن',
            'mri' => 'ام‌آر‌آی',
            'ultrasound' => 'سونوگرافی',
            'pet' => 'پت اسکن',
            'spect' => 'اسپکت',
            'mammogram' => 'ماموگرافی',
            'dental' => 'دندانپزشکی',
            'other' => 'سایر',
        ];
        return $labels[$this->image_type] ?? $this->image_type;
    }

    public function getModalityLabelAttribute(): string
    {
        $labels = [
            'DX' => 'رادیوگرافی دیجیتال',
            'CR' => 'رادیوگرافی کامپیوتری',
            'CT' => 'سی‌تی اسکن',
            'MR' => 'ام‌آر‌آی',
            'US' => 'سونوگرافی',
            'PT' => 'پت اسکن',
            'NM' => 'پزشکی هسته‌ای',
            'MG' => 'ماموگرافی',
            'IO' => 'رادیوگرافی داخل دهانی',
        ];
        return $labels[$this->modality] ?? $this->modality;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [];
        if ($this->clinic) {
            $parts[] = $this->clinic->address;
        }
        if ($this->city) {
            $parts[] = $this->city->name;
        }
        if ($this->province) {
            $parts[] = $this->province->name;
        }
        return implode('، ', $parts);
    }

    // ========== Scopes ==========

    public function scopeByClinic($query, $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('image_type', $type);
    }

    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }

    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeConfidential($query, $isConfidential = true)
    {
        return $query->where('is_confidential', $isConfidential);
    }



    // ========== Methods ==========

    public function isImage(): bool
    {
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        return in_array($this->mime_type, $imageTypes);
    }

    public function isDICOM(): bool
    {
        return $this->mime_type === 'application/dicom' ||
            $this->modality === 'CT' ||
            $this->modality === 'MR' ||
            $this->modality === 'DX';
    }

    public function markAsConfidential(): void
    {
        $this->update(['is_confidential' => true]);
    }

    public function markAsPublic(): void
    {
        $this->update(['is_confidential' => false]);
    }
}
