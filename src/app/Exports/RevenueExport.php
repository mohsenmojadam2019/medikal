<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RevenueExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Invoice::with(['patient.user', 'appointment']);

        if (isset($this->filters['from_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['from_date']);
        }

        if (isset($this->filters['to_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['to_date']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ردیف',
            'شماره فاکتور',
            'بیمار',
            'مبلغ (تومان)',
            'مالیات (تومان)',
            'تخفیف (تومان)',
            'مبلغ کل (تومان)',
            'وضعیت',
            'تاریخ ثبت',
            'تاریخ پرداخت',
        ];
    }

    public function map($invoice): array
    {
        static $row = 0;
        $row++;

        return [
            $row,
            $invoice->invoice_number,
            $invoice->patient->full_name ?? '—',
            number_format($invoice->amount ?? 0),
            number_format($invoice->tax ?? 0),
            number_format($invoice->discount ?? 0),
            number_format($invoice->total_amount ?? 0),
            $invoice->status_label,
            $invoice->created_at->format('Y/m/d'),
            $invoice->paid_at?->format('Y/m/d') ?? '—',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
