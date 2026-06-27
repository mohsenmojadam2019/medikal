<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\ManagementDashboardService;
use App\Http\Resources\DashboardStatsResource;
use App\Http\Resources\DashboardChartResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ManagementDashboardController extends Controller
{
    use ApiResponse;

    protected ManagementDashboardService $dashboardService;

    public function __construct(ManagementDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
        $this->middleware(['auth:sanctum', 'role:admin|super_admin']);
    }

    /**
     * دریافت آمار کلی داشبورد
     */
    public function stats(Request $request)
    {
        try {
            $stats = $this->dashboardService->getStats($request->all());
            
            return $this->success([
                'stats' => new DashboardStatsResource($stats),
                'timestamp' => now()->toDateTimeString(),
            ], 'آمار داشبورد با موفقیت دریافت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت داده‌های نمودارها
     */
    public function charts(Request $request)
    {
        try {
            $charts = $this->dashboardService->getChartData($request->all());
            
            return $this->success([
                'charts' => new DashboardChartResource($charts),
                'timestamp' => now()->toDateTimeString(),
            ], 'داده‌های نمودار با موفقیت دریافت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت آمار سریع (برای ویجت‌ها)
     */
    public function quickStats()
    {
        try {
            $stats = $this->dashboardService->getStats();

            $quickStats = [
                'appointments_today' => $stats['appointments']['today'],
                'patients_today' => $stats['patients']['new_today'],
                'revenue_today' => $stats['revenue']['today'],
                'available_beds' => $stats['hospital']['available_beds'],
                'pending_lab_orders' => $stats['laboratory']['pending_orders'],
                'critical_results' => $stats['laboratory']['critical_results'],
                'unread_notifications' => $stats['alerts']['unread'],
                'average_rating' => $stats['ratings']['average'],
            ];

            return $this->success($quickStats, 'آمار سریع با موفقیت دریافت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت فعالیت‌های اخیر
     */
    public function recentActivities(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $chartData = $this->dashboardService->getChartData($request->all());
            $activities = $chartData['recent_activities'] ?? [];

            return $this->success($activities, 'فعالیت‌های اخیر با موفقیت دریافت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت آمار پزشکان برتر
     */
    public function topDoctors(Request $request)
    {
        try {
            $limit = $request->get('limit', 5);
            $chartData = $this->dashboardService->getChartData(['limit' => $limit]);
            $topDoctors = $chartData['top_doctors'] ?? [];

            return $this->success($topDoctors, 'پزشکان برتر با موفقیت دریافت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت خلاصه عملکرد (برای گزارش سریع)
     */
    public function summary()
    {
        try {
            $stats = $this->dashboardService->getStats();

            $summary = [
                'period' => [
                    'today' => now()->format('Y-m-d'),
                    'this_month' => [
                        'start' => now()->startOfMonth()->format('Y-m-d'),
                        'end' => now()->endOfMonth()->format('Y-m-d'),
                    ],
                ],
                'appointments' => [
                    'total' => $stats['appointments']['total'],
                    'today' => $stats['appointments']['today'],
                    'completion_rate' => $stats['appointments']['total'] > 0
                        ? round(($stats['appointments']['completed'] / $stats['appointments']['total']) * 100, 1)
                        : 0,
                ],
                'revenue' => [
                    'total' => $stats['revenue']['total'],
                    'this_month' => $stats['revenue']['this_month'],
                    'pending' => $stats['revenue']['pending'],
                ],
                'patients' => [
                    'total' => $stats['patients']['total'],
                    'new_this_month' => $stats['patients']['new_this_month'],
                ],
                'hospital' => [
                    'occupancy_rate' => $stats['hospital']['occupancy_rate'],
                    'active_admissions' => $stats['hospital']['active_admissions'],
                ],
                'rating' => [
                    'average' => $stats['ratings']['average'],
                    'total' => $stats['ratings']['total'],
                ],
            ];

            return $this->success($summary, 'خلاصه عملکرد با موفقیت دریافت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
