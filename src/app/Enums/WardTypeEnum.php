<?php

namespace App\Enums;

enum WardTypeEnum: string
{
    case GENERAL = 'general';
    case PRIVATE = 'private';
    case VIP = 'vip';
    case ICU = 'icu';
    case CCU = 'ccu';
    case NICU = 'nicu';
    case PICU = 'picu';
    case SURGERY = 'surgery';
    case MATERNITY = 'maternity';
    case PEDIATRICS = 'pediatrics';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'عمومی',
            self::PRIVATE => 'خصوصی',
            self::VIP => 'VIP',
            self::ICU => 'ICU (مراقبت‌های ویژه)',
            self::CCU => 'CCU (مراقبت‌های قلبی)',
            self::NICU => 'NICU (مراقبت نوزادان)',
            self::PICU => 'PICU (مراقبت کودکان)',
            self::SURGERY => 'جراحی',
            self::MATERNITY => 'زایمان',
            self::PEDIATRICS => 'کودکان',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::GENERAL => 'primary',
            self::PRIVATE => 'success',
            self::VIP => 'warning',
            self::ICU, self::CCU, self::NICU, self::PICU => 'danger',
            self::SURGERY => 'info',
            self::MATERNITY => 'pink',
            self::PEDIATRICS => 'teal',
        };
    }

    public function dailyRate(): float
    {
        return match ($this) {
            self::GENERAL => 1000000,
            self::PRIVATE => 2000000,
            self::VIP => 5000000,
            self::ICU => 8000000,
            self::CCU => 7000000,
            self::NICU => 6000000,
            self::PICU => 6500000,
            self::SURGERY => 2500000,
            self::MATERNITY => 3000000,
            self::PEDIATRICS => 1500000,
        };
    }
}
