<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'event',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'route',
        'method',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    public function scopeByModel($query, $modelType, $modelId = null)
    {
        $query->where('model_type', $modelType);
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        return $query;
    }

    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function getEventLabelAttribute(): string
    {
        $labels = [
            'created' => 'ایجاد',
            'updated' => 'بروزرسانی',
            'deleted' => 'حذف',
            'restored' => 'بازیابی',
            'login' => 'ورود',
            'logout' => 'خروج',
            'viewed' => 'مشاهده',
            'exported' => 'خروجی',
        ];
        return $labels[$this->event] ?? $this->event;
    }

    public function getModelLabelAttribute(): string
    {
        if (!$this->model_type) return '—';
        $models = [
            'App\Models\User' => 'کاربر',
            'App\Models\Doctor' => 'پزشک',
            'App\Models\Patient' => 'بیمار',
            'App\Models\Appointment' => 'نوبت',
            'App\Models\Prescription' => 'نسخه',
            'App\Models\Invoice' => 'فاکتور',
            'App\Models\Admission' => 'پذیرش',
        ];
        return $models[$this->model_type] ?? class_basename($this->model_type);
    }

    public function getChangesCountAttribute(): int
    {
        if (!$this->old_values || !$this->new_values) return 0;
        return count(array_diff_assoc($this->new_values, $this->old_values));
    }
}
