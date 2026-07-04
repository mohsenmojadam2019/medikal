<?php
// app/Models/Rating.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rating extends Model
{

    protected $fillable = [
        'tenant_id',
        'doctor_id',
        'patient_id',
        'appointment_id',
        'score',
        'comment',
        'categories',
        'is_anonymous',
        'is_approved', // ✅ اضافه کن
        'reply',
        'replied_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'is_anonymous' => 'boolean',
        'is_approved' => 'boolean',
        'categories' => 'array',
        'replied_at' => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeHighScore($query, $minScore = 4)
    {
        return $query->where('score', '>=', $minScore);
    }

    public function scopeWithoutReply($query)
    {
        return $query->whereNull('reply');
    }
}
