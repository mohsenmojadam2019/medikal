<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Prescription;
use App\Models\Admission;
use App\Models\LabOrder;
use App\Models\PharmacyOrder;
use App\Models\Rating;
use App\Models\Wallet;
use App\Models\Notification;
use App\Models\LabResult;
use App\Models\Drug;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ManagementDashboardService
{
    /**
     * دریافت آمار کلی داشبورد مدیریت
     */
    public function getStats(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);

        return [
            'appointments' => $this->getAppointmentStats($dateRange),
            'patients' => $this->getPatientStats($dateRange),
            'doctors' => $this->getDoctorStats(),
            'revenue' => $this->getRevenueStats($dateRange),
            'hospital' => $this->getHospitalStats(),
            'laboratory' => $this->getLabStats(),
            'pharmacy' => $this->getPharmacyStats(),
            'prescriptions' => $this->getPrescriptionStats(),
            'wallet' => $this->getWalletStats(),
            'ratings' => $this->getRatingStats($dateRange),
            'alerts' => $this->getAlertStats(),
        ];
    }

    /**
     * دریافت داده‌های نمودارها
     */
    public function getChartData(array $filters = []): array
    {
        $days = $filters['days'] ?? 30;
        $dateRange = $this->getDateRange($filters);

        return [
            'appointments_trend' => $this->getAppointmentsTrend($days),
            'revenue_trend' => $this->getRevenueTrend($days),
            'appointment_status_distribution' => $this->getStatusDistribution(),
            'top_doctors' => $this->getTopDoctors(),
            'recent_activities' => $this->getRecentActivities(),
            'patient_growth' => $this->getPatientGrowth($days),
        ];
    }

    // ============================================================
    // PRIVATE METHODS - STATS
    // ============================================================

    private function getAppointmentStats(array $dateRange): array
    {
        $query = Appointment::query();

        return [
            'total' => $query->count(),
            'today' => (clone $query)->whereDate('date', today())->count(),
            'this_week' => (clone $query)->whereBetween('date', $dateRange['week'])->count(),
            'this_month' => (clone $query)->whereBetween('date', $dateRange['month'])->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'confirmed' => (clone $query)->where('status', 'confirmed')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'no_show' => (clone $query)->where('status', 'no_show')->count(),
            'upcoming' => (clone $query)->whereIn('status', ['pending', 'confirmed'])
                ->whereDate('date', '>=', today())
                ->count(),
        ];
    }

    private function getPatientStats(array $dateRange): array
    {
        $query = Patient::query();

        return [
            'total' => $query->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'new_today' => (clone $query)->whereDate('created_at', today())->count(),
            'new_this_week' => (clone $query)->whereBetween('created_at', $dateRange['week'])->count(),
            'new_this_month' => (clone $query)->whereBetween('created_at', $dateRange['month'])->count(),
            'verified' => (clone $query)->whereNotNull('verified_at')->count(),
        ];
    }

    private function getDoctorStats(): array
    {
        $query = Doctor::query();

        return [
            'total' => $query->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'available' => (clone $query)->where('is_available', true)->count(),
            'verified' => (clone $query)->where('is_verified', true)->count(),
            'on_leave' => (clone $query)->where('is_available', false)->where('is_active', true)->count(),
        ];
    }

    private function getRevenueStats(array $dateRange): array
    {
        $query = Invoice::where('status', 'paid');

        return [
            'today' => (clone $query)->whereDate('paid_at', today())->sum('total_amount'),
            'this_week' => (clone $query)->whereBetween('paid_at', $dateRange['week'])->sum('total_amount'),
            'this_month' => (clone $query)->whereBetween('paid_at', $dateRange['month'])->sum('total_amount'),
            'this_year' => (clone $query)->whereYear('paid_at', date('Y'))->sum('total_amount'),
            'total' => (clone $query)->sum('total_amount'),
            'pending' => Invoice::where('status', 'issued')->sum('total_amount'),
            'overdue' => Invoice::where('status', 'overdue')->sum('total_amount'),
        ];
    }

    private function getHospitalStats(): array
    {
        $query = Admission::query();

        return [
            'total_admissions' => $query->count(),
            'active_admissions' => (clone $query)->whereIn('status', ['admitted', 'in_progress'])->count(),
            'discharged_today' => (clone $query)->whereDate('discharged_at', today())->count(),
            'available_beds' => \App\Models\Bed::where('status', 'available')->count(),
            'occupancy_rate' => $this->calculateOccupancyRate(),
        ];
    }

    private function getLabStats(): array
    {
        $query = LabOrder::query();

        return [
            'total_orders' => $query->count(),
            'pending_orders' => (clone $query)->whereIn('status', ['pending', 'waiting_payment', 'paid'])->count(),
            'completed_orders' => (clone $query)->where('status', 'completed')->count(),
            'critical_results' => LabResult::where('is_critical', true)->count(),
            'abnormal_results' => LabResult::where('is_abnormal', true)->count(),
        ];
    }

    private function getPharmacyStats(): array
    {
        $query = PharmacyOrder::query();

        return [
            'total_orders' => $query->count(),
            'pending_orders' => (clone $query)->whereIn('status', ['pending', 'checking', 'payment_pending'])->count(),
            'completed_orders' => (clone $query)->where('status', 'delivered')->count(),
            'low_stock_drugs' => Drug::where('stock', '<', 10)->where('is_active', true)->count(),
        ];
    }

    private function getPrescriptionStats(): array
    {
        $query = Prescription::query();

        return [
            'total' => $query->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'expiring_soon' => (clone $query)->expiringSoon()->count(),
            'expired' => (clone $query)->where('status', 'expired')->count(),
        ];
    }

    private function getWalletStats(): array
    {
        return [
            'total_balance' => Wallet::sum('balance'),
            'total_transactions' => \App\Models\WalletTransaction::count(),
            'today_transactions' => \App\Models\WalletTransaction::whereDate('created_at', today())->count(),
            'active_wallets' => Wallet::where('is_active', true)->count(),
        ];
    }

    private function getRatingStats(array $dateRange): array
    {
        $query = Rating::query();

        return [
            'average' => round((clone $query)->avg('score') ?? 0, 1),
            'total' => $query->count(),
            'today' => (clone $query)->whereDate('created_at', today())->count(),
            'without_reply' => (clone $query)->whereNull('reply')->count(),
        ];
    }

    private function getAlertStats(): array
    {
        return [
            'critical' => Notification::where('priority', 'urgent')
                ->where('is_read', false)
                ->count(),
            'unread' => Notification::where('is_read', false)->count(),
        ];
    }

    // ============================================================
    // PRIVATE METHODS - CHARTS
    // ============================================================

    private function getAppointmentsTrend(int $days): array
    {
        $data = [];
        $labels = [];
        $completed = [];
        $cancelled = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');

            $total = Appointment::whereDate('date', $dateString)->count();
            $completedCount = Appointment::whereDate('date', $dateString)
                ->where('status', 'completed')
                ->count();
            $cancelledCount = Appointment::whereDate('date', $dateString)
                ->where('status', 'cancelled')
                ->count();

            $data[] = $total;
            $completed[] = $completedCount;
            $cancelled[] = $cancelledCount;
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'completed' => $completed,
            'cancelled' => $cancelled,
        ];
    }

    private function getRevenueTrend(int $days): array
    {
        $data = [];
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');

            $revenue = Invoice::where('status', 'paid')
                ->whereDate('paid_at', $dateString)
                ->sum('total_amount');

            $data[] = $revenue;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getStatusDistribution(): array
    {
        $statuses = ['pending', 'confirmed', 'arrived', 'in_progress', 'completed', 'cancelled', 'no_show'];
        $labels = [
            'pending' => 'در انتظار',
            'confirmed' => 'تایید شده',
            'arrived' => 'حاضر',
            'in_progress' => 'در حال ویزیت',
            'completed' => 'انجام شده',
            'cancelled' => 'لغو شده',
            'no_show' => 'حاضر نشده',
        ];
        $colors = [
            'pending' => '#F59E0B',
            'confirmed' => '#3B82F6',
            'arrived' => '#8B5CF6',
            'in_progress' => '#06B6D4',
            'completed' => '#22C55E',
            'cancelled' => '#EF4444',
            'no_show' => '#9CA3AF',
        ];

        $data = [];
        $labelList = [];
        $colorList = [];

        foreach ($statuses as $status) {
            $count = Appointment::where('status', $status)->count();
            if ($count > 0) {
                $data[] = $count;
                $labelList[] = $labels[$status];
                $colorList[] = $colors[$status];
            }
        }

        return [
            'labels' => $labelList,
            'data' => $data,
            'colors' => $colorList,
        ];
    }

    private function getTopDoctors(int $limit = 5): array
    {
        return Doctor::with(['user', 'specialty'])
            ->where('is_active', true)
            ->where('is_verified', true)
            ->withCount(['appointments' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->full_name,
                    'specialty' => $doctor->specialty?->name,
                    'rating' => $doctor->rating,
                    'total_reviews' => $doctor->total_reviews,
                    'completed_appointments' => $doctor->appointments_count ?? 0,
                    'consultation_fee' => $doctor->consultation_fee,
                    'avatar' => $doctor->profile_image_thumb ?? 'https://ui-avatars.com/api/?name=' . urlencode($doctor->full_name),
                ];
            })
            ->toArray();
    }

    private function getRecentActivities(int $limit = 10): array
    {
        $activities = [];

        // 1. نوبت‌های جدید
        $appointments = Appointment::with(['patient.user', 'doctor.user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'appointment',
                    'title' => 'نوبت جدید',
                    'description' => "بیمار {$item->patient->full_name} با دکتر {$item->doctor->full_name}",
                    'time' => $item->created_at->diffForHumans(),
                    'icon' => '📅',
                    'color' => 'blue',
                ];
            });

        // 2. پذیرش‌های جدید
        $admissions = Admission::with(['patient.user', 'doctor.user'])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'admission',
                    'title' => 'پذیرش جدید',
                    'description' => "بیمار {$item->patient->full_name} توسط دکتر {$item->doctor->full_name}",
                    'time' => $item->created_at->diffForHumans(),
                    'icon' => '🏥',
                    'color' => 'green',
                ];
            });

        // 3. نتایج آزمایش جدید
        $labResults = LabResult::with(['labOrder.patient', 'labTest'])
            ->where('is_critical', true)
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'lab_result',
                    'title' => 'نتیجه بحرانی',
                    'description' => "تست {$item->labTest->name} برای بیمار {$item->labOrder->patient->full_name}",
                    'time' => $item->created_at->diffForHumans(),
                    'icon' => '⚠️',
                    'color' => 'red',
                ];
            });

        // 4. ترخیص‌ها
        $discharges = \App\Models\Discharge::with(['admission.patient'])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'discharge',
                    'title' => 'ترخیص بیمار',
                    'description' => "بیمار {$item->admission->patient->full_name} ترخیص شد",
                    'time' => $item->created_at->diffForHumans(),
                    'icon' => '✅',
                    'color' => 'green',
                ];
            });

        // 5. فرم‌های جدید
        $forms = \App\Models\FormResponse::with(['patient.user', 'digitalForm'])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'form',
                    'title' => 'فرم جدید',
                    'description' => "فرم {$item->digitalForm->title} توسط بیمار {$item->patient->full_name}",
                    'time' => $item->created_at->diffForHumans(),
                    'icon' => '📋',
                    'color' => 'purple',
                ];
            });

        // 6. پرداخت‌ها
        $payments = Invoice::with(['patient.user'])
            ->where('status', 'paid')
            ->orderBy('paid_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'payment',
                    'title' => 'پرداخت جدید',
                    'description' => "مبلغ " . number_format($item->total_amount) . " تومان توسط بیمار {$item->patient->full_name}",
                    'time' => $item->paid_at->diffForHumans(),
                    'icon' => '💰',
                    'color' => 'gold',
                ];
            });

        // ترکیب و مرتب‌سازی
        $activities = collect()
            ->merge($appointments)
            ->merge($admissions)
            ->merge($labResults)
            ->merge($discharges)
            ->merge($forms)
            ->merge($payments)
            ->sortByDesc(function ($item) {
                return strtotime($item['time']);
            })
            ->take($limit)
            ->values()
            ->toArray();

        return $activities;
    }

    private function getPatientGrowth(int $days): array
    {
        $data = [];
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');

            $count = Patient::whereDate('created_at', '<=', $dateString)->count();
            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    private function getDateRange(array $filters): array
    {
        $now = Carbon::now();

        return [
            'week' => [
                $now->copy()->startOfWeek()->toDateString(),
                $now->copy()->endOfWeek()->toDateString(),
            ],
            'month' => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
        ];
    }

    private function calculateOccupancyRate(): float
    {
        $totalBeds = \App\Models\Bed::where('is_active', true)->count();
        $occupiedBeds = \App\Models\Bed::where('status', 'occupied')->count();

        if ($totalBeds == 0) return 0;

        return round(($occupiedBeds / $totalBeds) * 100, 1);
    }
}
