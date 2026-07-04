<?php
// app/Models/Setting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'tenant_id',
    ];

    protected $casts = [
        'value' => 'json',
    ];
}
