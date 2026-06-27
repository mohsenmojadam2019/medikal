<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelemedicineFile extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'description',
    ];

    public function session()
    {
        return $this->belongsTo(TelemedicineSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFileUrlAttribute(): string
    {
        return \Storage::url($this->file_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
