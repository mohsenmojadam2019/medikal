<?php

namespace App\Models\OR;

use Illuminate\Database\Eloquent\Model;

class SurgerySchedule extends Model
{
    protected $table = 'surgery_schedules';

    protected $fillable = [
        'operation_room_id',
        'patient_id',
        'doctor_id',
        'surgeon_id',
        'anesthesiologist_id',
        'assistant_doctor_id',
        'surgery_type',
        'diagnosis',
        'procedure',
        'priority',
        'scheduled_date',
        'scheduled_time',
        'estimated_duration',
        'actual_duration',
        'status',
        'notes',
        'pre_op_notes',
        'post_op_notes',
        'metadata',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime',
        'estimated_duration' => 'integer',
        'actual_duration' => 'integer',
        'metadata' => 'array',
    ];

    public function operationRoom()
    {
        return $this->belongsTo(OperationRoom::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function surgeon()
    {
        return $this->belongsTo(Doctor::class, 'surgeon_id');
    }

    public function anesthesiologist()
    {
        return $this->belongsTo(Doctor::class, 'anesthesiologist_id');
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'scheduled' => 'برنامه‌ریزی شده',
            'in_progress' => 'در حال انجام',
            'completed' => 'تکمیل شده',
            'cancelled' => 'لغو شده',
            'postponed' => 'به تعویق افتاده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getPriorityLabelAttribute(): string
    {
        $labels = [
            'routine' => 'معمولی',
            'urgent' => 'فوری',
            'emergency' => 'اورژانسی',
        ];
        return $labels[$this->priority] ?? $this->priority;
    }
}
