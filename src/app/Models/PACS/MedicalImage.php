<?php

namespace App\Models\PACS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalImage extends Model
{
    use SoftDeletes;

    protected $table = 'medical_images';

    protected $fillable = [
        'patient_id',
        'doctor_id',
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
    ];

    protected $casts = [
        'file_size' => 'integer',
        'study_date' => 'datetime',
        'is_confidential' => 'boolean',
        'metadata' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

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
}
