<?php

namespace App\Http\Controllers\Api\BI;

use App\Http\Controllers\Controller;
use App\Services\BI\BIAnalyticsService;
use App\Services\BI\CustomReportService;
use App\Services\Backup\BackupService;
use App\Services\LogStorage\LogStorageService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BIController extends Controller
{
    use ApiResponse;

    protected BIAnalyticsService $biService;
    protected CustomReportService $reportService;
    protected BackupService $backupService;
    protected LogStorageService $logService;

    public function __construct(
        BIAnalyticsService $biService,
        CustomReportService $reportService,
        BackupService $backupService,
        LogStorageService $logService
    ) {
        $this->biService = $biService;
        $this->reportService = $reportService;
        $this->backupService = $backupService;
        $this->logService = $logService;
        $this->middleware(['auth:sanctum', 'role:admin|super_admin']);
    }

    // ============================================================
    // PREDICTIVE ANALYTICS
    // ============================================================

    public function predictAppointments(Request $request)
    {
        try {
            $result = $this->biService->predictAppointments($request->all());
            return $this->success($result, 'پیش‌بینی نوبت‌ها با موفقیت انجام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function forecastRevenue(Request $request)
    {
        try {
            $result = $this->biService->forecastRevenue($request->all());
            return $this->success($result, 'پیش‌بینی درآمد با موفقیت انجام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function segmentPatients(Request $request)
    {
        try {
            $result = $this->biService->segmentPatients($request->all());
            return $this->success($result, 'بخش‌بندی بیماران با موفقیت انجام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function analyzeDoctors(Request $request)
    {
        try {
            $result = $this->biService->analyzeDoctorPerformance($request->all());
            return $this->success($result, 'تحلیل عملکرد پزشکان با موفقیت انجام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getAnalytics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:appointment_prediction,revenue_forecast,patient_segment,doctor_performance',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $result = $this->biService->getAnalytics($request->type, $request->get('limit', 10));
            return $this->success($result, 'تحلیل‌ها با موفقیت دریافت شدند');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // CUSTOM REPORTS
    // ============================================================

    public function reports(Request $request)
    {
        $reports = $this->reportService->getReports($request->all(), $request->get('per_page', 20));
        return $this->success($reports);
    }

    public function createReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:custom,predefined',
            'config' => 'required|array',
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'chart_type' => 'nullable|string',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['created_by'] = auth()->id();
            $report = $this->reportService->createReport($data);
            return $this->success($report, 'گزارش با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateReport(Request $request, $id)
    {
        try {
            $report = BIReport::findOrFail($id);
            $report = $this->reportService->updateReport($report, $request->all());
            return $this->success($report, 'گزارش با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deleteReport($id)
    {
        try {
            $report = BIReport::findOrFail($id);
            $this->reportService->deleteReport($report);
            return $this->success(null, 'گزارش با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function generateReport(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'nullable|in:pdf,excel,csv',
            'filters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $report = BIReport::findOrFail($id);
            $format = $request->get('format', 'pdf');
            return $this->reportService->generateReport($report, $request->get('filters', []), $format);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // REPORT SCHEDULING
    // ============================================================

    public function createSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bi_report_id' => 'required|exists:bi_reports,id',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'format' => 'nullable|in:pdf,excel,csv',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $schedule = $this->reportService->createSchedule($request->all());
            return $this->success($schedule, 'زمان‌بندی گزارش با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateSchedule(Request $request, $id)
    {
        try {
            $schedule = BIReportSchedule::findOrFail($id);
            $schedule = $this->reportService->updateSchedule($schedule, $request->all());
            return $this->success($schedule, 'زمان‌بندی گزارش با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deleteSchedule($id)
    {
        try {
            $schedule = BIReportSchedule::findOrFail($id);
            $this->reportService->deleteSchedule($schedule);
            return $this->success(null, 'زمان‌بندی گزارش با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // BACKUP
    // ============================================================

    public function backupDatabase()
    {
        try {
            $result = $this->backupService->backupDatabase();
            return $this->success($result, 'بک‌آپ دیتابیس با موفقیت انجام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function backupFiles(Request $request)
    {
        try {
            $paths = $request->get('paths', []);
            $result = $this->backupService->backupFiles($paths);
            return $this->success($result, 'بک‌آپ فایل‌ها با موفقیت انجام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function restoreBackup($id)
    {
        try {
            $result = $this->backupService->restoreBackup($id);
            return $this->success($result, 'بک‌آپ با موفقیت بازیابی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function backupHistory(Request $request)
    {
        $history = $this->backupService->getBackupHistory($request->all(), $request->get('per_page', 20));
        return $this->success($history);
    }

    public function cleanupBackups(Request $request)
    {
        try {
            $days = $request->get('days', 30);
            $count = $this->backupService->cleanupOldBackups($days);
            return $this->success(['count' => $count], "{$count} بک‌آپ قدیمی پاک شد");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // AUDIT LOG
    // ============================================================

    public function auditLogs(Request $request)
    {
        $logs = $this->logService->getAuditLogs($request->all(), $request->get('per_page', 50));
        return $this->success($logs);
    }

    public function logActivity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event' => 'required|string',
            'model_type' => 'nullable|string',
            'model_id' => 'nullable|integer',
            'old_values' => 'nullable|array',
            'new_values' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $log = $this->logService->log(
                $request->event,
                $request->model_type,
                $request->model_id,
                $request->old_values,
                $request->new_values,
                $request->metadata
            );
            return $this->success($log, 'لاگ با موفقیت ثبت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // LOG ARCHIVE
    // ============================================================

    public function archiveLogs(Request $request)
    {
        try {
            $type = $request->get('type', 'laravel');
            $result = $this->logService->archiveLogs($type);
            return $this->success($result, "{$result['archived']} فایل لاگ آرشیو شد");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function archivedLogs(Request $request)
    {
        $logs = $this->logService->getArchivedLogs($request->all(), $request->get('per_page', 20));
        return $this->success($logs);
    }

    public function restoreArchivedLog($id)
    {
        try {
            $result = $this->logService->restoreArchivedLog($id);
            return $this->success($result, 'فایل آرشیو با موفقیت بازیابی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function cleanupArchivedLogs(Request $request)
    {
        try {
            $days = $request->get('days', 90);
            $count = $this->logService->cleanupArchivedLogs($days);
            return $this->success(['count' => $count], "{$count} فایل آرشیو قدیمی پاک شد");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // STATS
    // ============================================================

    public function stats()
    {
        try {
            $stats = $this->logService->getStats();
            return $this->success($stats, 'آمار با موفقیت دریافت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
