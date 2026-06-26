<?php

namespace App\Enums;

enum InvoiceStatusEnum: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case OVERDUE = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'پیش‌نویس',
            self::ISSUED => 'صادر شده',
            self::PAID => 'پرداخت شده',
            self::CANCELLED => 'لغو شده',
            self::OVERDUE => 'سررسید گذشته',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::ISSUED => 'warning',
            self::PAID => 'success',
            self::CANCELLED => 'danger',
            self::OVERDUE => 'danger',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    public function isPending(): bool
    {
        return in_array($this, [self::DRAFT, self::ISSUED]);
    }
}
