<?php

namespace App\Models\OR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperationRoom extends Model
{
    use SoftDeletes;

    protected $table = 'operation_rooms';

    protected $fillable = [
        'name',
        'code',
        'floor',
        'type',
        'capacity',
        'equipment',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'equipment' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function schedules()
    {
        return $this->hasMany(SurgerySchedule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
