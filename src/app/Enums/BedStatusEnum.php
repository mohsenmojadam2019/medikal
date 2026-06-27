<?php

namespace App\Enums;

enum BedStatusEnum: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case RESERVED = 'reserved';
    case MAINTENANCE = 'maintenance';
    case CLEANING = 'cleaning';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'خالی',
            self::OCCUPIED => 'اشغال',
            self::RESERVED => 'رزرو',
            self::MAINTENANCE => 'تعمیر',
            self::CLEANING => 'در حال نظافت',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::OCCUPIED => 'danger',
            self::RESERVED => 'warning',
            self::MAINTENANCE => 'secondary',
            self::CLEANING => 'info',
        };
    }
}
