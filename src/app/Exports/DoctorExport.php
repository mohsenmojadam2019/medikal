<?php

namespace App\Exports;

use App\Models\Doctor;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DoctorExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Doctor::with(['user', 'specialty']);

        if (isset($this->filters['search'])) {
            $query->search($this->filters['search']);
        }

        if (isset($this->filters['specialty_id'])) {
            $query->bySpecialty($this->filters['specialty_id']);
        }

        if (isset($this->filters['is_available'])) {
            $query->where('is_available', $this->filters['is_available']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ردیف',
            'نام و نام خانوادگی',
            'تخصص',
            'شماره نظام پزشکی',
            'شماره موبایل',
            'هزینه ویزیت (تومان)',
            'تعداد بیماران',
            'تعداد نوبت‌ها',
            'امتیاز',
            'وضعیت',
        ];
    }

    public function map($doctor): array
    {
        static $row = 0;
        $row++;

        return [
            $row,
            $doctor->full_name,
            $doctor->specialty?->name ?? '—',
            $doctor->license_number,
            $doctor->user?->mobile ?? '—',
            number_format($doctor->consultation_fee ?? 0),
            $doctor->patients()->count(),
            $doctor->appointments()->count(),
            $doctor->rating ?? 0,
            $doctor->is_available ? 'فعال' : 'غیرفعال',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
