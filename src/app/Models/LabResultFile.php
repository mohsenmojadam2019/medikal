<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabResultFile extends Model
{
    protected $fillable = [
        'tenant_id',
        'lab_result_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // ========== Relationships ==========
    public function labResult()
    {
        return $this->belongsTo(LabResult::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ========== Accessors ==========
    public function getFileUrlAttribute(): string
    {
        return \Storage::url($this->file_path);
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

    public function getFileIconAttribute(): string
    {
        $extensions = [
            'pdf' => '📄',
            'doc' => '📝',
            'docx' => '📝',
            'xls' => '📊',
            'xlsx' => '📊',
            'jpg' => '🖼️',
            'jpeg' => '🖼️',
            'png' => '🖼️',
            'gif' => '🖼️',
            'txt' => '📃',
        ];

        $ext = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        return $extensions[$ext] ?? '📎';
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'
        ]);
    }

    public function isPDF(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
