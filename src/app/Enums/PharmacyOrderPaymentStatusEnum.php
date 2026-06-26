<?php

namespace App\Enums;

enum PharmacyOrderPaymentStatusEnum: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار پرداخت',
            self::PAID => 'پرداخت شده',
            self::FAILED => 'ناموفق',
            self::REFUNDED => 'عودت داده شده',
        };
    }
}
