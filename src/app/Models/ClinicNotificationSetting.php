<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class ClinicNotificationSetting extends Model{protected $fillable=['clinic_id','event_key','panel_enabled','sms_enabled','panel_title','panel_template','sms_template'];protected $casts=['panel_enabled'=>'boolean','sms_enabled'=>'boolean'];public function clinic(){return $this->belongsTo(Clinic::class);}}