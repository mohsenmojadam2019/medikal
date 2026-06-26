<?php

namespace App\Enums;

enum ReportTypeEnum: string
{
    case APPOINTMENTS = 'appointments';
    case PATIENTS = 'patients';
    case DOCTORS = 'doctors';
    case PRESCRIPTIONS = 'prescriptions';
    case PHARMACY_ORDERS = 'pharmacy_orders';
    case INVOICES = 'invoices';
    case REVENUE = 'revenue';

    public function label(): string
    {
        return match ($this) {
            self::APPOINTMENTS => 'گزارش نوبت‌ها',
            self::PATIENTS => 'گزارش بیماران',
            self::DOCTORS => 'گزارش پزشکان',
            self::PRESCRIPTIONS => 'گزارش نسخه‌ها',
            self::PHARMACY_ORDERS => 'گزارش سفارشات داروخانه',
            self::INVOICES => 'گزارش فاکتورها',
            self::REVENUE => 'گزارش درآمد',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::APPOINTMENTS => '📅',
            self::PATIENTS => '👤',
            self::DOCTORS => '👨‍⚕️',
            self::PRESCRIPTIONS => '💊',
            self::PHARMACY_ORDERS => '🏥',
            self::INVOICES => '📄',
            self::REVENUE => '💰',
        };
    }
}
