<?php

namespace App\Enums;

enum LabPriorityEnum: string
{
    case ROUTINE = 'routine';
    case URGENT = 'urgent';
    case STAT = 'stat';

    public function label(): string
    {
        return match ($this) {
            self::ROUTINE => 'معمولی',
            self::URGENT => 'فوری',
            self::STAT => 'اورژانسی',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ROUTINE => 'secondary',
            self::URGENT => 'warning',
            self::STAT => 'danger',
        };
    }

    public function maxHours(): int
    {
        return match ($this) {
            self::ROUTINE => 24,
            self::URGENT => 6,
            self::STAT => 1,
        };
    }
}
