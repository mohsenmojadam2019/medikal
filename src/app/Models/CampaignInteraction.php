<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignInteraction extends Model
{
    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'patient_id',
        'channel',
        'action',
        'content',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function getChannelLabelAttribute(): string
    {
        $labels = [
            'sms' => 'پیامک',
            'email' => 'ایمیل',
            'push' => 'نوتیفیکیشن',
            'social' => 'شبکه‌های اجتماعی',
        ];
        return $labels[$this->channel] ?? $this->channel;
    }

    public function getActionLabelAttribute(): string
    {
        $labels = [
            'sent' => 'ارسال شده',
            'opened' => 'باز شده',
            'clicked' => 'کلیک شده',
            'converted' => 'تبدیل شده',
            'bounced' => 'برگشت خورده',
        ];
        return $labels[$this->action] ?? $this->action;
    }

    public function scopeByCampaign($query, $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
}
