<?php

namespace App\Models\AiChat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ChatFile extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'chat_files';

    protected $fillable = [
        'session_id',
        'user_id',
        'message_id',
        'original_name',
        'file_name',
        'file_size',
        'mime_type',
        'file_type',
        'expires_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'expires_at' => 'datetime',
    ];

    protected $attributes = [
        'file_type' => 'other',
    ];

    public function session()
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function message()
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('chat_files')
            ->useDisk('public')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                // تبدیل برای تصاویر
                if (str_starts_with($media->mime_type, 'image/')) {
                    $this->addMediaConversion('thumb')
                        ->width(150)
                        ->height(150)
                        ->sharpen(10);
                    
                    $this->addMediaConversion('medium')
                        ->width(400)
                        ->height(400);
                }
            });
    }

    public function getUrl(): ?string
    {
        $media = $this->getFirstMedia('chat_files');
        return $media ? $media->getUrl() : null;
    }

    public function getThumbUrl(): ?string
    {
        $media = $this->getFirstMedia('chat_files');
        return $media ? $media->getUrl('thumb') : null;
    }

    public function getMediumUrl(): ?string
    {
        $media = $this->getFirstMedia('chat_files');
        return $media ? $media->getUrl('medium') : null;
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size * 1024;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    // حذف فایل از Media Library هنگام حذف رکورد
    protected static function booted()
    {
        static::deleting(function ($file) {
            $file->clearMediaCollection('chat_files');
        });
    }
}
