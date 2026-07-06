<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Pharmacy\PharmacyReportService;
use App\Models\Pharmacy;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PharmacyReportController extends Controller
{
    use ApiResponse;

    protected PharmacyReportService $reportService;

    public function __construct(PharmacyReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * گزارش کلی
     */
    public function overview(Request $request, $pharmacyId)
    {
        try {
            $pharmacy = Pharmacy::findOrFail($pharmacyId);
            
            // بررسی دسترسی
            $user = auth()->user();
            if (!$user->isAdmin() && $pharmacy->user_id !== $user->id) {
                return $this->error('شما دسترسی به این گزارش ندارید', 403);
            }

            $dateRange = $request->only(['start', 'end']);
            $report = $this->reportService->getOverview($pharmacyId, $dateRange);

            return $this->success($report);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * گزارش فروش محصولات
     */
    public function productSales(Request $request, $pharmacyId)
    {
        try {
            $pharmacy = Pharmacy::findOrFail($pharmacyId);
            
            $user = auth()->user();
            if (!$user->isAdmin() && $pharmacy->user_id !== $user->id) {
                return $this->error('شما دسترسی به این گزارش ندارید', 403);
            }

            $dateRange = $request->only(['start', 'end']);
            $report = $this->reportService->getProductSalesReport($pharmacyId, $dateRange);

            return $this->success($report);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * گزارش موجودی
     */
    public function inventory(Request $request, $pharmacyId)
    {
        try {
            $pharmacy = Pharmacy::findOrFail($pharmacyId);
            
            $user = auth()->user();
            if (!$user->isAdmin() && $pharmacy->user_id !== $user->id) {
                return $this->error('شما دسترسی به این گزارش ندارید', 403);
            }

            $report = $this->reportService->getInventoryReport($pharmacyId);

            return $this->success($report);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * گزارش مالی
     */
    public function financial(Request $request, $pharmacyId)
    {
        try {
            $pharmacy = Pharmacy::findOrFail($pharmacyId);
            
            $user = auth()->user();
            if (!$user->isAdmin() && $pharmacy->user_id !== $user->id) {
                return $this->error('شما دسترسی به این گزارش ندارید', 403);
            }

            $dateRange = $request->only(['start', 'end']);
            $report = $this->reportService->getFinancialReport($pharmacyId, $dateRange);

            return $this->success($report);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * گزارش روزانه
     */
    public function daily(Request $request, $pharmacyId)
    {
        try {
            $pharmacy = Pharmacy::findOrFail($pharmacyId);
            
            $user = auth()->user();
            if (!$user->isAdmin() && $pharmacy->user_id !== $user->id) {
                return $this->error('شما دسترسی به این گزارش ندارید', 403);
            }

            $date = $request->date ?? now()->format('Y-m-d');
            $report = $this->reportService->getDailyReport($pharmacyId, $date);

            return $this->success($report);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}
