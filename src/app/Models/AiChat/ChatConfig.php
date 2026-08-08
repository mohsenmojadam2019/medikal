<?php
namespace App\Models\AiChat;
use Illuminate\Database\Eloquent\Model;
class ChatConfig extends Model { protected $fillable=['key','value','type','description','is_editable','category','default_value']; protected function casts():array{return['is_editable'=>'boolean'];} }
