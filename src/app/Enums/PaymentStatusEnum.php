<?php

namespace App\Enums;

enum PaymentStatusEnum: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار',
            self::SUCCESS => 'موفق',
            self::FAILED => 'ناموفق',
            self::REFUNDED => 'عودت داده شده',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
}
