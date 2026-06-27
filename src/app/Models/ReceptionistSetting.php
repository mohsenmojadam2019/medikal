<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceptionistSetting extends Model
{
    protected $fillable = [
        'clinic_id',
        'allow_walk_in',
        'allow_phone_booking',
        'print_appointment_card',
        'max_walk_in_per_day',
        'default_appointment_duration',
        'notification_settings',
        'display_settings',
    ];

    protected $casts = [
        'allow_walk_in' => 'boolean',
        'allow_phone_booking' => 'boolean',
        'print_appointment_card' => 'boolean',
        'max_walk_in_per_day' => 'integer',
        'default_appointment_duration' => 'integer',
        'notification_settings' => 'array',
        'display_settings' => 'array',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
