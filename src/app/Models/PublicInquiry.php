<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicInquiry extends Model
{
    protected $fillable = ['type', 'locale', 'name', 'phone', 'email', 'subject', 'message', 'status', 'ip_address', 'user_agent'];
}
