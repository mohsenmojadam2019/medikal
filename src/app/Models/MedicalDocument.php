<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MedicalDocument extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'ehr_record_id',
        'title',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'category',
        'description',
        'is_private',
        'uploaded_at',
        'metadata',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'file_size' => 'integer',
        'uploaded_at' => 'datetime',
        'metadata' => 'array',
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

    public function ehrRecord()
    {
        return $this->belongsTo(EHRRecord::class);
    }

    // ========== Accessors ==========
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getCategoryLabelAttribute(): string
    {
        $labels = [
            'lab_result' => 'نتیجه آزمایش',
            'imaging' => 'تصویربرداری',
            'prescription' => 'نسخه',
            'referral' => 'ارجاع',
            'other' => 'سایر',
        ];
        return $labels[$this->category] ?? $this->category;
    }

    public function getFileSizeFormattedAttribute(): string
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

    // ========== Scopes ==========
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    // ========== Methods ==========
    public function isImage(): bool
    {
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        return in_array($this->file_type, $imageTypes);
    }

    public function isPDF(): bool
    {
        return $this->file_type === 'application/pdf';
    }

    protected static function booted()
    {
        static::creating(function ($document) {
            if (empty($document->uploaded_at)) {
                $document->uploaded_at = now();
            }
        });
    }
}
