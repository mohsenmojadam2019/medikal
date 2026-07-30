<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'slot_duration',
        'max_slots_per_day',
        'is_active',
        'is_special',
        'special_date',
        'special_reason',
    ];

    protected $casts = [
        'doctor_id' => 'integer',
        'day_of_week' => 'integer',
        'slot_duration' => 'integer',
        'max_slots_per_day' => 'integer',
        'is_active' => 'boolean',
        'is_special' => 'boolean',
        'special_date' => 'date',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(
            Doctor::class
        );
    }

    public function scopeForDoctor(
        Builder $query,
        int $doctorId
    ): Builder {
        return $query->where(
            'doctor_id',
            $doctorId
        );
    }

    public function scopeWeekly(
        Builder $query
    ): Builder {
        return $query->where(
            'is_special',
            false
        );
    }

    public function scopeSpecial(
        Builder $query
    ): Builder {
        return $query->where(
            'is_special',
            true
        );
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'is_active',
            true
        );
    }
}
