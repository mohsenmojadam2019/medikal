<?php
namespace App\Models\AiChat;
use Illuminate\Database\Eloquent\Model;
class MedicalQuery extends Model { protected $fillable=['user_id','session_id','question','response','category','severity','detected_symptoms','suggested_actions','is_handled','handled_by','handled_at','ai_confidence','metadata'];protected function casts():array{return['detected_symptoms'=>'array','suggested_actions'=>'array','is_handled'=>'boolean','handled_at'=>'datetime','metadata'=>'array'];} }
