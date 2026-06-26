<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * آمار کلی سیستم (برای ادمین)
     */
    public function getAdminStats(): array
    {
        $now = now();
        $today = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth();

        return [
            'users' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'new_this_month' => User::where('created_at', '>=', $monthStart)->count(),
            ],
            'doctors' => [
                'total' => Doctor::count(),
                'active' => Doctor::where('is_available', true)->count(),
                'verified' => Doctor::where('is_verified', true)->count(),
                'new_this_month' => Doctor::where('created_at', '>=', $monthStart)->count(),
            ],
            'patients' => [
                'total' => Patient::count(),
                'active' => Patient::where('is_active', true)->count(),
                'verified' => Patient::whereNotNull('verified_at')->count(),
                'new_this_month' => Patient::where('created_at', '>=', $monthStart)->count(),
            ],
            'appointments' => [
                'total' => Appointment::count(),
                'today' => Appointment::whereDate('date', $today)->count(),
                'pending' => Appointment::where('status', Appointment::STATUS_PENDING)->count(),
                'confirmed' => Appointment::where('status', Appointment::STATUS_CONFIRMED)->count(),
                'completed' => Appointment::where('status', Appointment::STATUS_COMPLETED)->count(),
                'cancelled' => Appointment::where('status', Appointment::STATUS_CANCELLED)->count(),
                'no_show' => Appointment::where('status', Appointment::STATUS_NO_SHOW)->count(),
                'this_month' => Appointment::where('created_at', '>=', $monthStart)->count(),
            ],
            'revenue' => [
                'today' => Invoice::whereDate('paid_at', $today)->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'this_month' => Invoice::where('paid_at', '>=', $monthStart)->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'total' => Invoice::where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'pending' => Invoice::where('status', Invoice::STATUS_ISSUED)->sum('total_amount'),
                'overdue' => Invoice::overdue()->sum('total_amount'),
            ],
            'prescriptions' => [
                'total' => Prescription::count(),
                'active' => Prescription::active()->count(),
                'expiring_soon' => Prescription::expiringSoon()->count(),
                'expired' => Prescription::expired()->count(),
            ],
        ];
    }

    /**
     * آمار پزشک (برای پنل پزشک)
     */
    public function getDoctorStats(int $doctorId): array
    {
        $now = now();
        $today = $now->toDateString();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();

        return [
            'appointments' => [
                'today' => Appointment::byDoctor($doctorId)->whereDate('date', $today)->count(),
                'this_week' => Appointment::byDoctor($doctorId)->whereDate('date', '>=', $weekStart)->count(),
                'this_month' => Appointment::byDoctor($doctorId)->where('created_at', '>=', $monthStart)->count(),
                'total' => Appointment::byDoctor($doctorId)->count(),
                'pending' => Appointment::byDoctor($doctorId)->where('status', Appointment::STATUS_PENDING)->count(),
                'confirmed' => Appointment::byDoctor($doctorId)->where('status', Appointment::STATUS_CONFIRMED)->count(),
                'completed' => Appointment::byDoctor($doctorId)->where('status', Appointment::STATUS_COMPLETED)->count(),
                'cancelled' => Appointment::byDoctor($doctorId)->where('status', Appointment::STATUS_CANCELLED)->count(),
                'no_show' => Appointment::byDoctor($doctorId)->where('status', Appointment::STATUS_NO_SHOW)->count(),
            ],
            'patients' => [
                'total' => Patient::byDoctor($doctorId)->count(),
                'new_this_month' => Patient::byDoctor($doctorId)->where('created_at', '>=', $monthStart)->count(),
                'active' => Patient::byDoctor($doctorId)->where('is_active', true)->count(),
            ],
            'revenue' => [
                'today' => Invoice::whereHas('appointment', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                })->whereDate('paid_at', $today)->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'this_month' => Invoice::whereHas('appointment', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                })->where('paid_at', '>=', $monthStart)->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'total' => Invoice::whereHas('appointment', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                })->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'pending' => Invoice::whereHas('appointment', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                })->where('status', Invoice::STATUS_ISSUED)->sum('total_amount'),
            ],
            'prescriptions' => [
                'total' => Prescription::byDoctor($doctorId)->count(),
                'active' => Prescription::byDoctor($doctorId)->active()->count(),
                'expiring_soon' => Prescription::byDoctor($doctorId)->expiringSoon()->count(),
                'expired' => Prescription::byDoctor($doctorId)->expired()->count(),
            ],
            'rating' => [
                'average' => Doctor::find($doctorId)?->rating ?? 0,
                'total_reviews' => Doctor::find($doctorId)?->total_reviews ?? 0,
            ],
        ];
    }

    /**
     * آمار بیمار (برای پنل بیمار)
     */
    public function getPatientStats(int $patientId): array
    {
        $now = now();
        $today = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth();

        return [
            'appointments' => [
                'total' => Appointment::byPatient($patientId)->count(),
                'upcoming' => Appointment::byPatient($patientId)->upcoming()->count(),
                'completed' => Appointment::byPatient($patientId)->where('status', Appointment::STATUS_COMPLETED)->count(),
                'cancelled' => Appointment::byPatient($patientId)->where('status', Appointment::STATUS_CANCELLED)->count(),
                'no_show' => Appointment::byPatient($patientId)->where('status', Appointment::STATUS_NO_SHOW)->count(),
            ],
            'prescriptions' => [
                'total' => Prescription::byPatient($patientId)->count(),
                'active' => Prescription::byPatient($patientId)->active()->count(),
                'completed' => Prescription::byPatient($patientId)->where('status', Prescription::STATUS_COMPLETED)->count(),
            ],
            'invoices' => [
                'total' => Invoice::byPatient($patientId)->count(),
                'paid' => Invoice::byPatient($patientId)->where('status', Invoice::STATUS_PAID)->count(),
                'pending' => Invoice::byPatient($patientId)->where('status', Invoice::STATUS_ISSUED)->count(),
                'total_paid' => Invoice::byPatient($patientId)->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'total_pending' => Invoice::byPatient($patientId)->where('status', Invoice::STATUS_ISSUED)->sum('total_amount'),
            ],
            'last_visit' => Appointment::byPatient($patientId)
                ->where('status', Appointment::STATUS_COMPLETED)
                ->orderBy('date', 'desc')
                ->first(),
            'next_appointment' => Appointment::byPatient($patientId)
                ->upcoming()
                ->orderBy('date', 'asc')
                ->first(),
        ];
    }

    /**
     * آمار نوبت‌های روزانه (برای نمودار)
     */
    public function getDailyAppointments(int $days = 30): array
    {
        $data = [];
        $endDate = now();
        $startDate = $endDate->copy()->subDays($days);

        $appointments = Appointment::select(
            DB::raw('DATE(date) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
            DB::raw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")
        )
        ->whereBetween('date', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->get();

        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        foreach ($dates as $date) {
            $row = $appointments->firstWhere('date', $date);
            $data[] = [
                'date' => $date,
                'total' => $row->total ?? 0,
                'completed' => $row->completed ?? 0,
                'cancelled' => $row->cancelled ?? 0,
            ];
        }

        return $data;
    }

    /**
     * آمار درآمد روزانه (برای نمودار)
     */
    public function getDailyRevenue(int $days = 30): array
    {
        $data = [];
        $endDate = now();
        $startDate = $endDate->copy()->subDays($days);

        $revenues = Invoice::select(
            DB::raw('DATE(paid_at) as date'),
            DB::raw('SUM(total_amount) as total')
        )
        ->where('status', Invoice::STATUS_PAID)
        ->whereBetween('paid_at', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->get();

        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        foreach ($dates as $date) {
            $row = $revenues->firstWhere('date', $date);
            $data[] = [
                'date' => $date,
                'revenue' => $row->total ?? 0,
            ];
        }

        return $data;
    }

    /**
     * توزیع نوبت‌ها بر اساس وضعیت
     */
    public function getAppointmentStatusDistribution(): array
    {
        $statuses = [
            Appointment::STATUS_PENDING,
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_ARRIVED,
            Appointment::STATUS_IN_PROGRESS,
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_NO_SHOW,
        ];

        $result = [];
        foreach ($statuses as $status) {
            $result[$status] = Appointment::where('status', $status)->count();
        }

        return $result;
    }
}
