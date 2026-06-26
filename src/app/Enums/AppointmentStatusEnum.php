<?php

namespace App\Enums;

enum AppointmentStatusEnum: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case ARRIVED = 'arrived';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار تایید',
            self::CONFIRMED => 'تایید شده',
            self::ARRIVED => 'حاضر در مطب',
            self::IN_PROGRESS => 'در حال ویزیت',
            self::COMPLETED => 'انجام شده',
            self::CANCELLED => 'لغو شده',
            self::NO_SHOW => 'حاضر نشده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'info',
            self::ARRIVED => 'primary',
            self::IN_PROGRESS => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'secondary',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::CONFIRMED,
            self::ARRIVED,
            self::IN_PROGRESS
        ]);
    }

    public function isPast(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::NO_SHOW
        ]);
    }
}
