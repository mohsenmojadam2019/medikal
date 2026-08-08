<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class HomeVisitRequest extends Model{
 use SoftDeletes;
 protected $fillable=['patient_id','doctor_id','request_number','status','address','patient_latitude','patient_longitude','doctor_latitude','doctor_longitude','scheduled_at','last_location_at','symptoms','contact_phone','amount','payment_status','metadata'];
 protected $casts=['patient_latitude'=>'float','patient_longitude'=>'float','doctor_latitude'=>'float','doctor_longitude'=>'float','scheduled_at'=>'datetime','last_location_at'=>'datetime','amount'=>'decimal:2','metadata'=>'array'];
 public function patient(){return $this->belongsTo(Patient::class);}
 public function doctor(){return $this->belongsTo(Doctor::class);}
};