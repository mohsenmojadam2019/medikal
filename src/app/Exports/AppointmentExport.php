<?php

namespace App\Exports;

use App\Models\Appointment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class AppointmentExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Appointment::with(['patient.user', 'doctor.user', 'doctor.specialty']);

        if (isset($this->filters['from_date'])) {
            $query->whereDate('date', '>=', $this->filters['from_date']);
        }

        if (isset($this->filters['to_date'])) {
            $query->whereDate('date', '<=', $this->filters['to_date']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['doctor_id'])) {
            $query->where('doctor_id', $this->filters['doctor_id']);
        }

        return $query->orderBy('date', 'desc');
    }

    public function headings(): array
    {
        return [
            'ردیف',
            'کد نوبت',
            'بیمار',
            'پزشک',
            'تخصص',
            'تاریخ',
            'ساعت',
            'وضعیت',
            'هزینه (تومان)',
            'تاریخ ثبت',
        ];
    }

    public function map($appointment): array
    {
        static $row = 0;
        $row++;

        return [
            $row,
            $appointment->code,
            $appointment->patient->full_name ?? '—',
            $appointment->doctor->full_name ?? '—',
            $appointment->doctor->specialty?->name ?? '—',
            $appointment->date->format('Y/m/d'),
            $appointment->start_time->format('H:i'),
            $appointment->status_label,
            number_format($appointment->fee ?? 0),
            $appointment->created_at->format('Y/m/d H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
