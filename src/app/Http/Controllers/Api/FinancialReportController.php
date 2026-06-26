<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Report\FinancialReportService;
use App\Models\Doctor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    use ApiResponse;

    protected FinancialReportService $reportService;

    public function __construct(FinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * گزارش درآمد پزشک
     */
    public function doctorIncome(Request $request, $doctorId)
    {
        $user = auth()->user();
        $doctor = Doctor::findOrFail($doctorId);

        if (!$user->isAdmin() && $doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $report = $this->reportService->getDoctorIncome($doctorId, $fromDate, $toDate);

        return $this->success($report);
    }

    /**
     * گزارش روزانه درآمد پزشک
     */
    public function dailyIncome(Request $request, $doctorId)
    {
        $user = auth()->user();
        $doctor = Doctor::findOrFail($doctorId);

        if (!$user->isAdmin() && $doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $days = $request->get('days', 30);
        $report = $this->reportService->getDailyIncome($doctorId, $days);

        return $this->success($report);
    }

    /**
     * گزارش ماهیانه درآمد پزشک
     */
    public function monthlyIncome(Request $request, $doctorId)
    {
        $user = auth()->user();
        $doctor = Doctor::findOrFail($doctorId);

        if (!$user->isAdmin() && $doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $months = $request->get('months', 12);
        $report = $this->reportService->getMonthlyIncome($doctorId, $months);

        return $this->success($report);
    }

    /**
     * گزارش نوبت‌های لغو شده
     */
    public function cancelledAppointments(Request $request, $doctorId)
    {
        $user = auth()->user();
        $doctor = Doctor::findOrFail($doctorId);

        if (!$user->isAdmin() && $doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $report = $this->reportService->getCancelledAppointments($doctorId, $fromDate, $toDate);

        return $this->success($report);
    }
}
