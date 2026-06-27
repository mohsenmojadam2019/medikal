<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelemedicineMessage extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'message',
        'type',
        'file_path',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(TelemedicineSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'text' => 'متن',
            'image' => 'تصویر',
            'file' => 'فایل',
            'prescription' => 'نسخه',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function getFileUrlAttribute(): string
    {
        return $this->file_path ? \Storage::url($this->file_path) : null;
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
