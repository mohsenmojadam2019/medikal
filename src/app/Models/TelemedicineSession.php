<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
class TelemedicineSession extends Model{
 use SoftDeletes;
 protected $fillable=['tenant_id','appointment_id','doctor_id','patient_id','session_id','room_name','status','started_at','ended_at','duration','notes','metadata']; protected $appends=['room_url']; protected $casts=['started_at'=>'datetime','ended_at'=>'datetime','metadata'=>'array'];
 protected static function booted(){static::creating(function($m){$m->session_id??=(string)Str::uuid();$m->room_name??='medikal-'.Str::lower(Str::random(24));});}
 public function doctor(){return $this->belongsTo(Doctor::class);} public function patient(){return $this->belongsTo(Patient::class);} public function appointment(){return $this->belongsTo(Appointment::class);} public function messages(){return $this->hasMany(TelemedicineMessage::class,'session_id');} public function files(){return $this->hasMany(TelemedicineFile::class,'session_id');}
 public function scopeByDoctor($q,$id){return $q->where('doctor_id',$id);} public function scopeByPatient($q,$id){return $q->where('patient_id',$id);} public function scopeActive($q){return $q->whereIn('status',['scheduled','waiting','in_progress']);} public function scopeToday($q){return $q->whereHas('appointment',fn($x)=>$x->whereDate('date',today()));}
 public function markAsWaiting(){$this->update(['status'=>'waiting']);} public function start(){$this->update(['status'=>'in_progress','started_at'=>now()]);} public function complete(){$this->update(['status'=>'completed','ended_at'=>now(),'duration'=>$this->started_at?now()->diffInMinutes($this->started_at):0]);} public function cancel(){$this->update(['status'=>'cancelled','ended_at'=>now()]);} public function getRoomUrlAttribute(){return 'https://meet.jit.si/'.rawurlencode($this->room_name);}
}
