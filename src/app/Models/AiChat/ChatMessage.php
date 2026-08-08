<?php

namespace App\Models\AiChat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['session_id', 'user_id', 'role', 'content', 'provider', 'model_used', 'tokens_used', 'response_time', 'is_emergency', 'is_medical', 'category', 'confidence_score', 'severity', 'metadata'];
    protected function casts(): array { return ['is_emergency' => 'boolean', 'is_medical' => 'boolean', 'metadata' => 'array']; }
    public function session() { return $this->belongsTo(ChatSession::class, 'session_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
