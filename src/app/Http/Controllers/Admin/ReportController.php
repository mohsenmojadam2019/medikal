<?php
// app/Http/Controllers/Admin/ReportController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Report\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    use ApiResponse;

    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * دریافت انواع گزارش‌ها
     */
    public function types()
    {
        try {
            $types = $this->reportService->getAvailableReports();
            $formattedTypes = [];

            foreach ($types as $key => $type) {
                $formattedTypes[] = [
                    'value' => $key,
                    'label' => $type['label'],
                    'icon' => $type['icon'],
                    'filters' => $type['filters'],
                    'formats' => $type['formats'],
                ];
            }

            return $this->success($formattedTypes);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * تولید گزارش
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:appointments,patients,doctors,revenue',
            'format' => 'required|string|in:excel,pdf',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'status' => 'nullable|string',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'specialty_id' => 'nullable|integer|exists:specialties,id',
            'search' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $this->reportService->getReportData(
                $request->type,
                $request->all()
            );

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * خروجی Excel
     */
    public function exportExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:appointments,patients,doctors,revenue',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'status' => 'nullable|string',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'specialty_id' => 'nullable|integer|exists:specialties,id',
            'search' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            return $this->reportService->generateExcel(
                $request->type,
                $request->all()
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * خروجی PDF
     */
    public function exportPdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:appointments,patients,doctors,revenue',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'status' => 'nullable|string',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'specialty_id' => 'nullable|integer|exists:specialties,id',
            'search' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            return $this->reportService->generatePDF(
                $request->type,
                $request->all()
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * خروجی PDF (Stream)
     */
    public function streamPdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:appointments,patients,doctors,revenue',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'status' => 'nullable|string',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'specialty_id' => 'nullable|integer|exists:specialties,id',
            'search' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            return $this->reportService->streamPDF(
                $request->type,
                $request->all()
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
