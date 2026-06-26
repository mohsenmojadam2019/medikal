<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyNotification extends Model
{
    protected $fillable = [
        'patient_id', 'order_id', 'type', 'title', 'message', 'data', 'is_read', 'sent_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function order()
    {
        return $this->belongsTo(PharmacyOrder::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }
}
