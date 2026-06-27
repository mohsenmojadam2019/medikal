<?php

namespace App\Enums;

enum AdmissionStatusEnum: string
{
    case PENDING = 'pending';
    case ADMITTED = 'admitted';
    case IN_PROGRESS = 'in_progress';
    case DISCHARGED = 'discharged';
    case TRANSFERRED = 'transferred';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار پذیرش',
            self::ADMITTED => 'پذیرش شده',
            self::IN_PROGRESS => 'در حال بستری',
            self::DISCHARGED => 'ترخیص شده',
            self::TRANSFERRED => 'منتقل شده',
            self::CANCELLED => 'لغو شده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ADMITTED => 'info',
            self::IN_PROGRESS => 'primary',
            self::DISCHARGED => 'success',
            self::TRANSFERRED => 'secondary',
            self::CANCELLED => 'danger',
        };
    }
}
