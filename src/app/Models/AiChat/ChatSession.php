<?php

namespace App\Models\AiChat;

use App\Enums\AiChat\ChatSessionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ChatSession extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'session_token', 'title', 'status', 'provider', 'model_used', 'category', 'expires_at', 'last_activity', 'message_count', 'metadata'];

    protected function casts(): array
    {
        return ['status' => ChatSessionStatus::class, 'expires_at' => 'datetime', 'last_activity' => 'datetime', 'metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $session) => $session->session_token ??= Str::random(64));
    }

    public function user() { return $this->belongsTo(User::class); }
    public function messages() { return $this->hasMany(ChatMessage::class, 'session_id'); }
    public function isExpired(): bool { return $this->expires_at?->isPast() ?? false; }
    public function incrementMessageCount(): void { $this->increment('message_count'); }
}
