<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'day_of_week', // 0=شنبه, 1=یکشنبه, ...
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'slot_duration',
        'max_slots_per_day',
        'is_active',
        'is_special', // برای تعطیلات/مرخصی
        'special_date', // تاریخ خاص (برای تعطیلات)
        'special_reason', // دلیل تعطیلات
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_special' => 'boolean',
        'special_date' => 'date',
        'slot_duration' => 'integer',
        'max_slots_per_day' => 'integer',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function getDayNameAttribute(): string
    {
        $days = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'];
        return $days[$this->day_of_week] ?? 'نامشخص';
    }

    public function getStartTimeFormattedAttribute(): string
    {
        return $this->start_time ? date('H:i', strtotime($this->start_time)) : '';
    }

    public function getEndTimeFormattedAttribute(): string
    {
        return $this->end_time ? date('H:i', strtotime($this->end_time)) : '';
    }

    public function getTotalSlotsAttribute(): int
    {
        if (!$this->start_time || !$this->end_time) return 0;
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        $duration = $this->slot_duration ?? 30;
        $totalMinutes = ($end - $start) / 60;

        if ($this->break_start && $this->break_end) {
            $breakStart = strtotime($this->break_start);
            $breakEnd = strtotime($this->break_end);
            $totalMinutes -= ($breakEnd - $breakStart) / 60;
        }

        return (int) floor($totalMinutes / $duration);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRegular($query)
    {
        return $query->where('is_special', false);
    }

    public function scopeSpecial($query)
    {
        return $query->where('is_special', true);
    }

    public function scopeByDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('special_date', $date);
    }

    public function isWorkingDay($dayOfWeek): bool
    {
        return $this->day_of_week === $dayOfWeek && $this->is_active && !$this->is_special;
    }

    public function isWithinWorkingHours($time): bool
    {
        if (!$this->start_time || !$this->end_time) return false;
        $checkTime = strtotime($time);
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);

        if ($checkTime < $start || $checkTime >= $end) return false;

        if ($this->break_start && $this->break_end) {
            $breakStart = strtotime($this->break_start);
            $breakEnd = strtotime($this->break_end);
            if ($checkTime >= $breakStart && $checkTime < $breakEnd) return false;
        }

        return true;
    }
}
