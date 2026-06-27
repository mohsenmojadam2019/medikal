<?php

namespace App\Enums;

enum LabResultStatusEnum: string
{
    case PENDING = 'pending';
    case PARTIAL = 'partial';
    case COMPLETED = 'completed';
    case ABNORMAL = 'abnormal';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار',
            self::PARTIAL => 'تکمیل بخشی',
            self::COMPLETED => 'تکمیل شده',
            self::ABNORMAL => 'غیرطبیعی',
            self::CRITICAL => 'بحرانی',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PARTIAL => 'info',
            self::COMPLETED => 'success',
            self::ABNORMAL => 'danger',
            self::CRITICAL => 'danger',
        };
    }

    public function isAbnormal(): bool
    {
        return in_array($this, [self::ABNORMAL, self::CRITICAL]);
    }
}
