<?php

namespace App\Models\BI;

use Illuminate\Database\Eloquent\Model;

class BIAnalytic extends Model
{
    protected $table = 'bi_analytics';

    protected $fillable = [
        'type',
        'name',
        'data',
        'metadata',
        'calculated_at',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('calculated_at', 'desc')->limit($limit);
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'appointment_prediction' => 'پیش‌بینی نوبت‌ها',
            'revenue_forecast' => 'پیش‌بینی درآمد',
            'patient_segment' => 'بخش‌بندی بیماران',
            'doctor_performance' => 'تحلیل عملکرد پزشکان',
        ];
        return $labels[$this->type] ?? $this->type;
    }
}
