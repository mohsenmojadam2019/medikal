<?php

namespace App\Enums;

enum AdmissionServiceTypeEnum: string
{
    case MEDICAL = 'medical';
    case PARACLINICAL = 'paraclinical';
    case SURGERY = 'surgery';
    case CONSULTATION = 'consultation';
    case NURSING = 'nursing';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MEDICAL => 'خدمات پزشکی',
            self::PARACLINICAL => 'پاراکلینیک',
            self::SURGERY => 'جراحی',
            self::CONSULTATION => 'مشاوره',
            self::NURSING => 'پرستاری',
            self::OTHER => 'سایر',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MEDICAL => 'primary',
            self::PARACLINICAL => 'info',
            self::SURGERY => 'danger',
            self::CONSULTATION => 'warning',
            self::NURSING => 'success',
            self::OTHER => 'secondary',
        };
    }
}
