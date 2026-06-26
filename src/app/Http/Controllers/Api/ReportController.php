<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Report\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse;

    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * لیست گزارش‌های موجود
     */
    public function types()
    {
        $types = $this->reportService->getAvailableReports();
        return $this->success($types);
    }

    /**
     * خروجی Excel
     */
    public function excel(Request $request)
    {
        $request->validate([
            'type' => 'required|in:appointments,patients,doctors,revenue',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'status' => 'nullable|string',
            'doctor_id' => 'nullable|exists:doctors,id',
            'specialty_id' => 'nullable|exists:specialties,id',
            'search' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $filters = $request->only([
                'from_date', 'to_date', 'status', 'doctor_id',
                'specialty_id', 'search', 'is_active'
            ]);

            return $this->reportService->generateExcel($request->type, $filters);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * خروجی PDF (دانلود)
     */
    public function pdf(Request $request)
    {
        $request->validate([
            'type' => 'required|in:appointments,patients,doctors,revenue',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'status' => 'nullable|string',
            'doctor_id' => 'nullable|exists:doctors,id',
            'specialty_id' => 'nullable|exists:specialties,id',
            'search' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $filters = $request->only([
                'from_date', 'to_date', 'status', 'doctor_id',
                'specialty_id', 'search', 'is_active'
            ]);

            return $this->reportService->generatePDF($request->type, $filters);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * خروجی PDF (نمایش آنلاین)
     */
    public function stream(Request $request)
    {
        $request->validate([
            'type' => 'required|in:appointments,patients,doctors,revenue',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'status' => 'nullable|string',
            'doctor_id' => 'nullable|exists:doctors,id',
            'specialty_id' => 'nullable|exists:specialties,id',
            'search' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $filters = $request->only([
                'from_date', 'to_date', 'status', 'doctor_id',
                'specialty_id', 'search', 'is_active'
            ]);

            return $this->reportService->streamPDF($request->type, $filters);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
