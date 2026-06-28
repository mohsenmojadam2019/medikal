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
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function getAdminStats(): array
    {
        $now = now();
        $today = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth();

        return [
            'users' => [
                'total' => User::whereHas('tenants', function($q) {
                    $q->where('tenant_id', $this->tenantId);
                })->count(),
                'active' => User::whereHas('tenants', function($q) {
                    $q->where('tenant_id', $this->tenantId);
                })->where('is_active', true)->count(),
                'new_this_month' => User::whereHas('tenants', function($q) {
                    $q->where('tenant_id', $this->tenantId);
                })->where('created_at', '>=', $monthStart)->count(),
            ],
            'doctors' => [
                'total' => Doctor::where('tenant_id', $this->tenantId)->count(),
                'active' => Doctor::where('tenant_id', $this->tenantId)->where('is_available', true)->count(),
                'verified' => Doctor::where('tenant_id', $this->tenantId)->where('is_verified', true)->count(),
                'new_this_month' => Doctor::where('tenant_id', $this->tenantId)->where('created_at', '>=', $monthStart)->count(),
            ],
            'patients' => [
                'total' => Patient::where('tenant_id', $this->tenantId)->count(),
                'active' => Patient::where('tenant_id', $this->tenantId)->where('is_active', true)->count(),
                'verified' => Patient::where('tenant_id', $this->tenantId)->whereNotNull('verified_at')->count(),
                'new_this_month' => Patient::where('tenant_id', $this->tenantId)->where('created_at', '>=', $monthStart)->count(),
            ],
            'appointments' => [
                'total' => Appointment::where('tenant_id', $this->tenantId)->count(),
                'today' => Appointment::where('tenant_id', $this->tenantId)->whereDate('date', $today)->count(),
                'pending' => Appointment::where('tenant_id', $this->tenantId)->where('status', Appointment::STATUS_PENDING)->count(),
                'confirmed' => Appointment::where('tenant_id', $this->tenantId)->where('status', Appointment::STATUS_CONFIRMED)->count(),
                'completed' => Appointment::where('tenant_id', $this->tenantId)->where('status', Appointment::STATUS_COMPLETED)->count(),
                'cancelled' => Appointment::where('tenant_id', $this->tenantId)->where('status', Appointment::STATUS_CANCELLED)->count(),
                'no_show' => Appointment::where('tenant_id', $this->tenantId)->where('status', Appointment::STATUS_NO_SHOW)->count(),
                'this_month' => Appointment::where('tenant_id', $this->tenantId)->where('created_at', '>=', $monthStart)->count(),
            ],
            'revenue' => [
                'today' => Invoice::where('tenant_id', $this->tenantId)->whereDate('paid_at', $today)->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'this_month' => Invoice::where('tenant_id', $this->tenantId)->where('paid_at', '>=', $monthStart)->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'total' => Invoice::where('tenant_id', $this->tenantId)->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'pending' => Invoice::where('tenant_id', $this->tenantId)->where('status', Invoice::STATUS_ISSUED)->sum('total_amount'),
                'overdue' => Invoice::where('tenant_id', $this->tenantId)->overdue()->sum('total_amount'),
            ],
            'prescriptions' => [
                'total' => Prescription::where('tenant_id', $this->tenantId)->count(),
                'active' => Prescription::where('tenant_id', $this->tenantId)->active()->count(),
                'expiring_soon' => Prescription::where('tenant_id', $this->tenantId)->expiringSoon()->count(),
                'expired' => Prescription::where('tenant_id', $this->tenantId)->expired()->count(),
            ],
        ];
    }

    public function getDoctorStats(int $doctorId): array
    {
        $now = now();
        $today = $now->toDateString();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();

        return [
            'appointments' => [
                'today' => Appointment::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->whereDate('date', $today)->count(),
                'this_week' => Appointment::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->whereDate('date', '>=', $weekStart)->count(),
                'this_month' => Appointment::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->where('created_at', '>=', $monthStart)->count(),
                'total' => Appointment::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->count(),
                'pending' => Appointment::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->where('status', Appointment::STATUS_PENDING)->count(),
                'confirmed' => Appointment::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->where('status', Appointment::STATUS_CONFIRMED)->count(),
                'completed' => Appointment::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->where('status', Appointment::STATUS_COMPLETED)->count(),
                'cancelled' => Appointment::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->where('status', Appointment::STATUS_CANCELLED)->count(),
                'no_show' => Appointment::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->where('status', Appointment::STATUS_NO_SHOW)->count(),
            ],
            'patients' => [
                'total' => Patient::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->count(),
                'new_this_month' => Patient::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->where('created_at', '>=', $monthStart)->count(),
                'active' => Patient::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->where('is_active', true)->count(),
            ],
            'revenue' => [
                'today' => Invoice::where('tenant_id', $this->tenantId)->whereHas('appointment', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                })->whereDate('paid_at', $today)->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'this_month' => Invoice::where('tenant_id', $this->tenantId)->whereHas('appointment', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                })->where('paid_at', '>=', $monthStart)->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'total' => Invoice::where('tenant_id', $this->tenantId)->whereHas('appointment', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                })->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'pending' => Invoice::where('tenant_id', $this->tenantId)->whereHas('appointment', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                })->where('status', Invoice::STATUS_ISSUED)->sum('total_amount'),
            ],
            'prescriptions' => [
                'total' => Prescription::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->count(),
                'active' => Prescription::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->active()->count(),
                'expiring_soon' => Prescription::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->expiringSoon()->count(),
                'expired' => Prescription::where('tenant_id', $this->tenantId)->byDoctor($doctorId)->expired()->count(),
            ],
            'rating' => [
                'average' => Doctor::where('tenant_id', $this->tenantId)->find($doctorId)?->rating ?? 0,
                'total_reviews' => Doctor::where('tenant_id', $this->tenantId)->find($doctorId)?->total_reviews ?? 0,
            ],
        ];
    }

    public function getPatientStats(int $patientId): array
    {
        $now = now();
        $today = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth();

        return [
            'appointments' => [
                'total' => Appointment::where('tenant_id', $this->tenantId)->byPatient($patientId)->count(),
                'upcoming' => Appointment::where('tenant_id', $this->tenantId)->byPatient($patientId)->upcoming()->count(),
                'completed' => Appointment::where('tenant_id', $this->tenantId)->byPatient($patientId)->where('status', Appointment::STATUS_COMPLETED)->count(),
                'cancelled' => Appointment::where('tenant_id', $this->tenantId)->byPatient($patientId)->where('status', Appointment::STATUS_CANCELLED)->count(),
                'no_show' => Appointment::where('tenant_id', $this->tenantId)->byPatient($patientId)->where('status', Appointment::STATUS_NO_SHOW)->count(),
            ],
            'prescriptions' => [
                'total' => Prescription::where('tenant_id', $this->tenantId)->byPatient($patientId)->count(),
                'active' => Prescription::where('tenant_id', $this->tenantId)->byPatient($patientId)->active()->count(),
                'completed' => Prescription::where('tenant_id', $this->tenantId)->byPatient($patientId)->where('status', Prescription::STATUS_COMPLETED)->count(),
            ],
            'invoices' => [
                'total' => Invoice::where('tenant_id', $this->tenantId)->byPatient($patientId)->count(),
                'paid' => Invoice::where('tenant_id', $this->tenantId)->byPatient($patientId)->where('status', Invoice::STATUS_PAID)->count(),
                'pending' => Invoice::where('tenant_id', $this->tenantId)->byPatient($patientId)->where('status', Invoice::STATUS_ISSUED)->count(),
                'total_paid' => Invoice::where('tenant_id', $this->tenantId)->byPatient($patientId)->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
                'total_pending' => Invoice::where('tenant_id', $this->tenantId)->byPatient($patientId)->where('status', Invoice::STATUS_ISSUED)->sum('total_amount'),
            ],
            'last_visit' => Appointment::where('tenant_id', $this->tenantId)->byPatient($patientId)
                ->where('status', Appointment::STATUS_COMPLETED)
                ->orderBy('date', 'desc')
                ->first(),
            'next_appointment' => Appointment::where('tenant_id', $this->tenantId)->byPatient($patientId)
                ->upcoming()
                ->orderBy('date', 'asc')
                ->first(),
        ];
    }

    public function getDailyAppointments(int $days = 30): array
    {
        $data = [];
        $endDate = now();
        $startDate = $endDate->copy()->subDays($days);

        $appointments = Appointment::where('tenant_id', $this->tenantId)
            ->select(
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

    public function getDailyRevenue(int $days = 30): array
    {
        $data = [];
        $endDate = now();
        $startDate = $endDate->copy()->subDays($days);

        $revenues = Invoice::where('tenant_id', $this->tenantId)
            ->select(
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
            $result[$status] = Appointment::where('tenant_id', $this->tenantId)->where('status', $status)->count();
        }

        return $result;
    }
}
