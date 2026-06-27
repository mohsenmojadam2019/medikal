<?php

namespace App\Models\BI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BIReport extends Model
{
    use SoftDeletes;

    protected $table = 'bi_reports';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'config',
        'filters',
        'columns',
        'chart_type',
        'is_public',
        'created_by',
    ];

    protected $casts = [
        'config' => 'array',
        'filters' => 'array',
        'columns' => 'array',
        'is_public' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schedules()
    {
        return $this->hasMany(BIReportSchedule::class);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function generateSlug(): string
    {
        $slug = \Illuminate\Support\Str::slug($this->name);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    protected static function booted()
    {
        static::creating(function ($report) {
            if (empty($report->slug)) {
                $report->slug = $report->generateSlug();
            }
        });
    }
}
