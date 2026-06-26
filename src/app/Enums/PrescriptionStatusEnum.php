<?php

namespace App\Enums;

enum PrescriptionStatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار تایید',
            self::ACTIVE => 'فعال',
            self::COMPLETED => 'تکمیل شده',
            self::CANCELLED => 'لغو شده',
            self::EXPIRED => 'منقضی شده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACTIVE => 'success',
            self::COMPLETED => 'info',
            self::CANCELLED => 'danger',
            self::EXPIRED => 'secondary',
        };
    }
}
