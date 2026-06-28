<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'assigned_to',
        'ticket_number',
        'subject',
        'message',
        'priority',
        'status',
        'category',
        'metadata',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getPriorityLabelAttribute(): string
    {
        $labels = [
            'low' => 'کم',
            'medium' => 'متوسط',
            'high' => 'بالا',
            'urgent' => 'فوری',
        ];
        return $labels[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute(): string
    {
        $colors = [
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'danger',
            'urgent' => 'danger',
        ];
        return $colors[$this->priority] ?? 'secondary';
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'open' => 'باز',
            'in_progress' => 'در حال بررسی',
            'resolved' => 'حل شده',
            'closed' => 'بسته',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'open' => 'danger',
            'in_progress' => 'warning',
            'resolved' => 'success',
            'closed' => 'secondary',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getCategoryLabelAttribute(): string
    {
        $labels = [
            'general' => 'عمومی',
            'technical' => 'فنی',
            'billing' => 'مالی',
            'feature' => 'درخواست ویژگی',
        ];
        return $labels[$this->category] ?? $this->category;
    }

    public function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $year = now()->format('y');
        $month = now()->format('m');
        $day = now()->format('d');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}{$day}-{$random}";
    }

    public function resolve(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function assignTo(int $userId): void
    {
        $this->update(['assigned_to' => $userId]);
    }

    protected static function booted()
    {
        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = $ticket->generateTicketNumber();
            }
        });
    }
}
