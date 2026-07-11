<?php

namespace App\Enums;

enum LabSampleTypeEnum: string
{
    case BLOOD = 'blood';
    case URINE = 'urine';
    case STOOL = 'stool';
    case SALIVA = 'saliva';
    case SPUTUM = 'sputum';
    case CSF = 'csf';
    case TISSUE = 'tissue';
    case SWAB = 'swab';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BLOOD => 'خون',
            self::URINE => 'ادرار',
            self::STOOL => 'مدفوع',
            self::SALIVA => 'بزاق',
            self::SPUTUM => 'خلط',
            self::CSF => 'مایع مغزی-نخاعی',
            self::TISSUE => 'بافت',
            self::SWAB => 'سواب',
            self::OTHER => 'سایر',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BLOOD => 'danger',
            self::URINE => 'warning',
            self::STOOL => 'brown',
            self::SALIVA => 'cyan',
            self::SPUTUM => 'orange',
            self::CSF => 'purple',
            self::TISSUE => 'magenta',
            self::SWAB => 'blue',
            self::OTHER => 'default',
        };
    }
}
