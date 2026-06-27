<?php

namespace App\Services\BI;

use App\Models\BI\BIReport;
use App\Models\BI\BIReportSchedule;
use App\Exports\CustomReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class CustomReportService
{
    // ============================================================
    // 1. REPORT CRUD
    // ============================================================

    public function createReport(array $data): BIReport
    {
        return BIReport::create($data);
    }

    public function updateReport(BIReport $report, array $data): BIReport
    {
        $report->update($data);
        return $report->fresh();
    }

    public function deleteReport(BIReport $report): void
    {
        $report->delete();
    }

    public function getReports(array $filters = [], int $perPage = 20)
    {
        $query = BIReport::with(['creator']);

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    // ============================================================
    // 2. GENERATE REPORT
    // ============================================================

    public function generateReport(BIReport $report, array $filters = [], string $format = 'pdf')
    {
        $data = $this->fetchReportData($report, $filters);

        return match ($format) {
            'pdf' => $this->generatePDF($report, $data),
            'excel' => $this->generateExcel($report, $data),
            'csv' => $this->generateCSV($report, $data),
            default => throw new \Exception("فرمت {$format} پشتیبانی نمی‌شود"),
        };
    }

    private function fetchReportData(BIReport $report, array $filters = [])
    {
        // بر اساس نوع گزارش، داده‌ها را دریافت کن
        $config = $report->config;
        $columns = $report->columns ?? [];

        // مثال: گزارش نوبت‌ها
        if ($config['source'] === 'appointments') {
            $query = \App\Models\Appointment::query();

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

            $data = $query->with(['patient.user', 'doctor.user'])->get();
        } else {
            // سایر منابع
            $data = collect();
        }

        return $data;
    }

    private function generatePDF(BIReport $report, $data)
    {
        $pdf = Pdf::loadView('bi.report-pdf', [
            'report' => $report,
            'data' => $data,
            'generated_at' => now(),
        ]);

        return $pdf->download($report->slug . '.pdf');
    }

    private function generateExcel(BIReport $report, $data)
    {
        return Excel::download(
            new CustomReportExport($report, $data),
            $report->slug . '.xlsx'
        );
    }

    private function generateCSV(BIReport $report, $data)
    {
        return Excel::download(
            new CustomReportExport($report, $data),
            $report->slug . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    // ============================================================
    // 3. REPORT SCHEDULING
    // ============================================================

    public function createSchedule(array $data): BIReportSchedule
    {
        return BIReportSchedule::create($data);
    }

    public function updateSchedule(BIReportSchedule $schedule, array $data): BIReportSchedule
    {
        $schedule->update($data);
        return $schedule->fresh();
    }

    public function deleteSchedule(BIReportSchedule $schedule): void
    {
        $schedule->delete();
    }

    public function processScheduledReports(): void
    {
        $schedules = BIReportSchedule::active()->get();

        foreach ($schedules as $schedule) {
            $shouldRun = $this->shouldRunSchedule($schedule);
            if ($shouldRun) {
                $this->runScheduledReport($schedule);
            }
        }
    }

    private function shouldRunSchedule(BIReportSchedule $schedule): bool
    {
        if (!$schedule->last_sent_at) return true;

        $lastSent = $schedule->last_sent_at;
        $now = now();

        return match ($schedule->frequency) {
            'daily' => $lastSent->diffInDays($now) >= 1,
            'weekly' => $lastSent->diffInWeeks($now) >= 1,
            'monthly' => $lastSent->diffInMonths($now) >= 1,
            'quarterly' => $lastSent->diffInMonths($now) >= 3,
            default => false,
        };
    }

    private function runScheduledReport(BIReportSchedule $schedule): void
    {
        try {
            $report = $schedule->report;
            $file = $this->generateReport($report, [], $schedule->format);

            // ارسال به ایمیل‌های دریافت‌کننده
            $this->sendReportToRecipients($schedule, $file);

            $schedule->update(['last_sent_at' => now()]);

        } catch (\Exception $e) {
            \Log::error('Scheduled report failed: ' . $e->getMessage());
        }
    }

    private function sendReportToRecipients(BIReportSchedule $schedule, $file): void
    {
        // ایمپلنت ارسال ایمیل
        \Log::info('Sending scheduled report', [
            'report_id' => $schedule->bi_report_id,
            'recipients' => $schedule->recipients,
            'format' => $schedule->format,
        ]);
    }
}
