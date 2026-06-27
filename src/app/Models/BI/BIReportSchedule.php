<?php

namespace App\Models\BI;

use Illuminate\Database\Eloquent\Model;

class BIReportSchedule extends Model
{
    protected $table = 'bi_report_schedules';

    protected $fillable = [
        'bi_report_id',
        'frequency',
        'recipients',
        'format',
        'is_active',
        'last_sent_at',
        'metadata',
    ];

    protected $casts = [
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function report()
    {
        return $this->belongsTo(BIReport::class, 'bi_report_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFrequencyLabelAttribute(): string
    {
        $labels = [
            'daily' => 'روزانه',
            'weekly' => 'هفتگی',
            'monthly' => 'ماهیانه',
            'quarterly' => 'فصلی',
        ];
        return $labels[$this->frequency] ?? $this->frequency;
    }

    public function getFormatLabelAttribute(): string
    {
        $labels = [
            'pdf' => 'PDF',
            'excel' => 'Excel',
            'csv' => 'CSV',
        ];
        return $labels[$this->format] ?? $this->format;
    }
}
