<?php
namespace App\Models\AiChat;
use Illuminate\Database\Eloquent\Model;
class AIPrompt extends Model { protected $table='ai_prompts';protected $fillable=['name','slug','category','system_prompt','user_prompt_template','version','is_active','is_default','priority','config','usage_count','created_by'];protected function casts():array{return['is_active'=>'boolean','is_default'=>'boolean','config'=>'array'];} }
