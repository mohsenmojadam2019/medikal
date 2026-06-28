<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'slug',
        'start_date',
        'end_date',
        'location',
        'max_attendees',
        'is_published',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_published' => 'boolean',
        'is_active' => 'boolean',
    ];
}
