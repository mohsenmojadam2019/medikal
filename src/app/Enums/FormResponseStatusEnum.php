<?php

namespace App\Enums;

enum FormResponseStatusEnum: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'پیش‌نویس',
            self::SUBMITTED => 'ارسال شده',
            self::COMPLETED => 'تکمیل شده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::SUBMITTED => 'warning',
            self::COMPLETED => 'success',
        };
    }
}
