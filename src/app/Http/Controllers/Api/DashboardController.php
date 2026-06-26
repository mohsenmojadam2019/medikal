<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * داشبورد ادمین
     */
    public function admin(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $stats = $this->dashboardService->getAdminStats();

        // داده‌های نمودار
        $dailyAppointments = $this->dashboardService->getDailyAppointments(30);
        $dailyRevenue = $this->dashboardService->getDailyRevenue(30);
        $statusDistribution = $this->dashboardService->getAppointmentStatusDistribution();

        return $this->success([
            'stats' => $stats,
            'charts' => [
                'daily_appointments' => $dailyAppointments,
                'daily_revenue' => $dailyRevenue,
                'status_distribution' => $statusDistribution,
            ],
        ]);
    }

    /**
     * داشبورد پزشک
     */
    public function doctor(Request $request)
    {
        $user = auth()->user();
        $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('شما پزشک نیستید', 403);
        }

        $stats = $this->dashboardService->getDoctorStats($doctor->id);

        return $this->success([
            'stats' => $stats,
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->full_name,
                'specialty' => $doctor->specialty?->name,
                'rating' => $doctor->rating,
                'total_reviews' => $doctor->total_reviews,
                'is_available' => $doctor->is_available,
                'consultation_fee' => $doctor->consultation_fee,
            ],
        ]);
    }

    /**
     * داشبورد بیمار
     */
    public function patient(Request $request)
    {
        $user = auth()->user();
        $patient = \App\Models\Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('شما بیمار نیستید', 403);
        }

        $stats = $this->dashboardService->getPatientStats($patient->id);

        return $this->success([
            'stats' => $stats,
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->full_name,
                'doctor' => $patient->doctor?->full_name,
            ],
        ]);
    }
}
