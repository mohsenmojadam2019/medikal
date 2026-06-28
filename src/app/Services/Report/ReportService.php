<?php

namespace App\Services\Report;

use App\Enums\ReportTypeEnum;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Exports\AppointmentExport;
use App\Exports\PatientExport;
use App\Exports\DoctorExport;
use App\Exports\RevenueExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ReportService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function getAvailableReports(): array
    {
        return [
            'appointments' => [
                'label' => 'گزارش نوبت‌ها',
                'icon' => '📅',
                'filters' => ['date_range', 'status', 'doctor'],
                'formats' => ['excel', 'pdf'],
            ],
            'patients' => [
                'label' => 'گزارش بیماران',
                'icon' => '👤',
                'filters' => ['search', 'doctor', 'status'],
                'formats' => ['excel', 'pdf'],
            ],
            'doctors' => [
                'label' => 'گزارش پزشکان',
                'icon' => '👨‍⚕️',
                'filters' => ['search', 'specialty', 'status'],
                'formats' => ['excel', 'pdf'],
            ],
            'revenue' => [
                'label' => 'گزارش درآمد',
                'icon' => '💰',
                'filters' => ['date_range', 'status'],
                'formats' => ['excel', 'pdf'],
            ],
        ];
    }

    public function generateExcel(string $type, array $filters = [])
    {
        try {
            $export = match ($type) {
                ReportTypeEnum::APPOINTMENTS->value => new AppointmentExport($filters),
                ReportTypeEnum::PATIENTS->value => new PatientExport($filters),
                ReportTypeEnum::DOCTORS->value => new DoctorExport($filters),
                ReportTypeEnum::REVENUE->value => new RevenueExport($filters),
                default => throw new \Exception('نوع گزارش نامعتبر است'),
            };

            $fileName = $this->getFileName($type, 'xlsx');
            return Excel::download($export, $fileName);

        } catch (\Exception $e) {
            Log::error('Excel export failed: ' . $e->getMessage(), [
                'tenant_id' => $this->tenantId,
                'type' => $type,
            ]);
            throw $e;
        }
    }

    public function generatePDF(string $type, array $filters = [])
    {
        try {
            $data = $this->getReportData($type, $filters);
            $view = $this->getPdfView($type);

            $pdf = Pdf::loadView($view, [
                'data' => $data,
                'filters' => $filters,
            ]);

            $pdf->setPaper('A4', 'landscape');
            $fileName = $this->getFileName($type, 'pdf');

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::error('PDF export failed: ' . $e->getMessage(), [
                'tenant_id' => $this->tenantId,
                'type' => $type,
            ]);
            throw $e;
        }
    }

    public function streamPDF(string $type, array $filters = [])
    {
        try {
            $data = $this->getReportData($type, $filters);
            $view = $this->getPdfView($type);

            $pdf = Pdf::loadView($view, [
                'data' => $data,
                'filters' => $filters,
            ]);

            $pdf->setPaper('A4', 'landscape');
            $fileName = $this->getFileName($type, 'pdf');

            return $pdf->stream($fileName);

        } catch (\Exception $e) {
            Log::error('PDF stream failed: ' . $e->getMessage(), [
                'tenant_id' => $this->tenantId,
                'type' => $type,
            ]);
            throw $e;
        }
    }

    protected function getReportData(string $type, array $filters)
    {
        return match ($type) {
            ReportTypeEnum::APPOINTMENTS->value => $this->getAppointmentsData($filters),
            ReportTypeEnum::PATIENTS->value => $this->getPatientsData($filters),
            ReportTypeEnum::DOCTORS->value => $this->getDoctorsData($filters),
            ReportTypeEnum::REVENUE->value => $this->getRevenueData($filters),
            default => collect(),
        };
    }

    protected function getAppointmentsData(array $filters)
    {
        $query = Appointment::where('tenant_id', $this->tenantId)
            ->with(['patient.user', 'doctor.user', 'doctor.specialty']);

        if (isset($filters['from_date'])) {
            $query->whereDate('date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('date', '<=', $filters['to_date']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    protected function getPatientsData(array $filters)
    {
        $query = Patient::where('tenant_id', $this->tenantId)
            ->with(['user', 'doctor.user']);

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    protected function getDoctorsData(array $filters)
    {
        $query = Doctor::where('tenant_id', $this->tenantId)
            ->with(['user', 'specialty']);

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['specialty_id'])) {
            $query->bySpecialty($filters['specialty_id']);
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', $filters['is_available']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    protected function getRevenueData(array $filters)
    {
        $query = Invoice::where('tenant_id', $this->tenantId)
            ->with(['patient.user', 'appointment']);

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    protected function getPdfView(string $type): string
    {
        return match ($type) {
            ReportTypeEnum::APPOINTMENTS->value => 'pdfs.appointment-pdf',
            default => 'pdfs.appointment-pdf',
        };
    }

    protected function getFileName(string $type, string $extension): string
    {
        $prefix = match ($type) {
            'appointments' => 'نوبت‌ها',
            'patients' => 'بیماران',
            'doctors' => 'پزشکان',
            'revenue' => 'درآمد',
            default => 'گزارش',
        };

        return $prefix . '-' . now()->format('Y-m-d-Hi') . '.' . $extension;
    }
}
