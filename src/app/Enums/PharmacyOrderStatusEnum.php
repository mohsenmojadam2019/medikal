<?php

namespace App\Enums;

enum PharmacyOrderStatusEnum: string
{
    case PENDING = 'pending';
    case CHECKING = 'checking';
    case PARTIAL_AVAILABLE = 'partial_available';
    case ALL_AVAILABLE = 'all_available';
    case PAYMENT_PENDING = 'payment_pending';
    case PAID = 'paid';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار بررسی',
            self::CHECKING => 'در حال بررسی موجودی',
            self::PARTIAL_AVAILABLE => 'بخشی از داروها موجود است',
            self::ALL_AVAILABLE => 'همه داروها موجود است',
            self::PAYMENT_PENDING => 'در انتظار پرداخت',
            self::PAID => 'پرداخت شده',
            self::PREPARING => 'در حال آماده‌سازی',
            self::READY => 'آماده تحویل',
            self::DELIVERED => 'تحویل شده',
            self::CANCELLED => 'لغو شده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING, self::CHECKING => 'warning',
            self::PARTIAL_AVAILABLE, self::ALL_AVAILABLE => 'info',
            self::PAYMENT_PENDING => 'warning',
            self::PAID, self::PREPARING, self::READY => 'primary',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
