<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'provider',
        'event_type',
        'payload',
        'response',
        'status_code',
        'error_message',
        'ip_address',
        'user_agent',
        'tenant_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];

    public function scopeProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status_code', 200);
    }

    public function scopeFailed($query)
    {
        return $query->where('status_code', '!=', 200);
    }
}
