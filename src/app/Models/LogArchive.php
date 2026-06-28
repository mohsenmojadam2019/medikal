<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogArchive extends Model
{
    protected $table = 'log_archives';

    protected $fillable = [
        'tenant_id',
        'file_name',
        'file_path',
        'file_size',
        'type',
        'date',
        'is_compressed',
        'archived_at',
        'metadata',
    ];

    protected $casts = [
        'is_compressed' => 'boolean',
        'date' => 'date',
        'archived_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function getFileSizeDisplayAttribute(): string
    {
        if (!$this->file_size) return '—';
        $bytes = (int) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'laravel' => 'لاگ لاراول',
            'audit' => 'لاگ حسابرسی',
            'backup' => 'لاگ بک‌آپ',
        ];
        return $labels[$this->type] ?? $this->type;
    }
}
