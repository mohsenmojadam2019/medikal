<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdvertisingEvent extends Model { protected $fillable = ['campaign_key','event_type','placement','locale','ip_address','user_agent']; }
