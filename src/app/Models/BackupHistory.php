<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupHistory extends Model
{
    protected $table = 'backup_history';

    protected $fillable = [
        'name',
        'type',
        'status',
        'file_path',
        'file_size',
        'duration',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function scopeSuccess($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'در انتظار',
            'running' => 'در حال اجرا',
            'completed' => 'تکمیل شده',
            'failed' => 'ناموفق',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'running' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
        ];
        return $colors[$this->status] ?? 'secondary';
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

    public function getDurationDisplayAttribute(): string
    {
        if (!$this->duration) return '—';
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        if ($minutes > 0) {
            return "{$minutes} دقیقه و {$seconds} ثانیه";
        }
        return "{$seconds} ثانیه";
    }

    public function markAsRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(string $filePath, int $fileSize): void
    {
        $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'completed_at' => now(),
            'duration' => $this->started_at->diffInSeconds(now()),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => now(),
            'duration' => $this->started_at->diffInSeconds(now()),
        ]);
    }
}
