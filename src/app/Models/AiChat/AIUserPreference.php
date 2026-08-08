<?php

namespace App\Models\AiChat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AIUserPreference extends Model
{
    protected $table = 'ai_user_preferences';
    protected $fillable = ['user_id', 'ai_provider_id', 'model'];
    public function user() { return $this->belongsTo(User::class); }
    public function provider() { return $this->belongsTo(AIProvider::class, 'ai_provider_id'); }
}
