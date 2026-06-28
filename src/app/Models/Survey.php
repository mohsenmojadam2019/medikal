<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'description',
        'type',
        'is_active',
        'questions',
        'settings',
        'start_date',
        'end_date',
        'max_attempts',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'questions' => 'array',
        'settings' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'max_attempts' => 'integer',
    ];

    public function responses()
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'فعال' : 'غیرفعال';
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'appointment' => 'نظرسنجی نوبت',
            'general' => 'نظرسنجی عمومی',
            'doctor' => 'نظرسنجی پزشک',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function getTotalResponsesAttribute(): int
    {
        return $this->responses()->count();
    }

    public function getAverageScoreAttribute(): float
    {
        return round($this->responses()->avg('score') ?? 0, 1);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function generateSlug(): string
    {
        return \Illuminate\Support\Str::slug($this->title) . '-' . time();
    }

    public function canPatientRespond($patientId): bool
    {
        $responseCount = $this->responses()
            ->where('patient_id', $patientId)
            ->count();

        return $responseCount < $this->max_attempts;
    }

    protected static function booted()
    {
        static::creating(function ($survey) {
            if (empty($survey->slug)) {
                $survey->slug = $survey->generateSlug();
            }
        });
    }
}
