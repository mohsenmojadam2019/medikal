<?php

namespace App\Exports;

use App\Models\Patient;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PatientExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Patient::with(['user', 'doctor.user']);

        if (isset($this->filters['search'])) {
            $query->search($this->filters['search']);
        }

        if (isset($this->filters['doctor_id'])) {
            $query->byDoctor($this->filters['doctor_id']);
        }

        if (isset($this->filters['is_active'])) {
            $query->where('is_active', $this->filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ردیف',
            'نام و نام خانوادگی',
            'کدملی',
            'شماره موبایل',
            'پزشک معالج',
            'گروه خونی',
            'تعداد نوبت‌ها',
            'وضعیت',
            'تاریخ ثبت',
        ];
    }

    public function map($patient): array
    {
        static $row = 0;
        $row++;

        return [
            $row,
            $patient->full_name,
            $patient->national_code ?? '—',
            $patient->phone ?? '—',
            $patient->doctor?->full_name ?? '—',
            $patient->blood_type ?? '—',
            $patient->appointments()->count(),
            $patient->is_active ? 'فعال' : 'غیرفعال',
            $patient->created_at->format('Y/m/d'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
