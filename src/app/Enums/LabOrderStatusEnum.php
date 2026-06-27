<?php

namespace App\Enums;

enum LabOrderStatusEnum: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case WAITING_PAYMENT = 'waiting_payment';
    case PAID = 'paid';
    case SCHEDULED = 'scheduled';
    case SAMPLE_COLLECTED = 'sample_collected';
    case PROCESSING = 'processing';
    case PARTIAL = 'partial';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'پیش‌نویس',
            self::PENDING => 'در انتظار تایید',
            self::WAITING_PAYMENT => 'در انتظار پرداخت',
            self::PAID => 'پرداخت شده',
            self::SCHEDULED => 'نوبت‌دهی شده',
            self::SAMPLE_COLLECTED => 'نمونه گرفته شده',
            self::PROCESSING => 'در حال پردازش',
            self::PARTIAL => 'تکمیل بخشی از نتایج',
            self::COMPLETED => 'تکمیل شده',
            self::CANCELLED => 'لغو شده',
            self::REJECTED => 'رد شده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::PENDING => 'warning',
            self::WAITING_PAYMENT => 'warning',
            self::PAID => 'info',
            self::SCHEDULED => 'primary',
            self::SAMPLE_COLLECTED => 'info',
            self::PROCESSING => 'info',
            self::PARTIAL => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::REJECTED => 'danger',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::WAITING_PAYMENT,
            self::PAID,
            self::SCHEDULED,
            self::SAMPLE_COLLECTED,
            self::PROCESSING,
            self::PARTIAL,
        ]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::REJECTED,
        ]);
    }

    public function requiresPayment(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::WAITING_PAYMENT,
        ]);
    }
}
