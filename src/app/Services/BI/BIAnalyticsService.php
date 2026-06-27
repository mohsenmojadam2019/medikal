<?php

namespace App\Services\BI;

use App\Models\BI\BIAnalytic;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BIAnalyticsService
{
    // ============================================================
    // 1. PREDICTIVE ANALYTICS - پیش‌بینی نوبت‌ها
    // ============================================================

    public function predictAppointments(array $params = []): array
    {
        $days = $params['days'] ?? 30;
        $doctorId = $params['doctor_id'] ?? null;

        // دریافت داده‌های تاریخی
        $historicalData = $this->getHistoricalAppointments($days, $doctorId);

        // تحلیل روند
        $trend = $this->analyzeTrend($historicalData);

        // پیش‌بینی
        $prediction = $this->calculatePrediction($historicalData, $trend);

        // ذخیره در دیتابیس
        $this->storeAnalytic('appointment_prediction', 'پیش‌بینی نوبت‌ها', [
            'historical' => $historicalData,
            'trend' => $trend,
            'prediction' => $prediction,
            'params' => $params,
        ]);

        return [
            'historical' => $historicalData,
            'trend' => $trend,
            'prediction' => $prediction,
            'summary' => $this->getPredictionSummary($prediction),
        ];
    }

    private function getHistoricalAppointments(int $days, ?int $doctorId): array
    {
        $query = Appointment::whereBetween('date', [
            Carbon::now()->subDays($days),
            Carbon::now(),
        ]);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $appointments = $query->get()->groupBy(function ($item) {
            return $item->date->format('Y-m-d');
        });

        $data = [];
        $current = Carbon::now()->subDays($days);
        for ($i = 0; $i < $days; $i++) {
            $date = $current->copy()->addDays($i)->format('Y-m-d');
            $data[] = [
                'date' => $date,
                'total' => $appointments->get($date, collect())->count(),
                'completed' => $appointments->get($date, collect())
                    ->where('status', 'completed')
                    ->count(),
                'cancelled' => $appointments->get($date, collect())
                    ->where('status', 'cancelled')
                    ->count(),
            ];
        }

        return $data;
    }

    private function analyzeTrend(array $data): array
    {
        $total = array_sum(array_column($data, 'total'));
        $days = count($data);
        $avg = $days > 0 ? $total / $days : 0;

        // محاسبه رشد
        $growth = 0;
        if ($days > 1) {
            $firstHalf = array_slice($data, 0, floor($days / 2));
            $secondHalf = array_slice($data, floor($days / 2));
            $firstAvg = array_sum(array_column($firstHalf, 'total')) / count($firstHalf);
            $secondAvg = array_sum(array_column($secondHalf, 'total')) / count($secondHalf);
            $growth = $firstAvg > 0 ? (($secondAvg - $firstAvg) / $firstAvg) * 100 : 0;
        }

        // تشخیص روزهای شلوغ
        $busyDays = [];
        foreach ($data as $day) {
            if ($day['total'] > $avg * 1.5) {
                $busyDays[] = $day['date'];
            }
        }

        return [
            'average' => round($avg, 1),
            'growth' => round($growth, 1),
            'trend' => $growth > 5 ? 'increasing' : ($growth < -5 ? 'decreasing' : 'stable'),
            'busy_days' => $busyDays,
            'total' => $total,
            'days' => $days,
        ];
    }

    private function calculatePrediction(array $data, array $trend): array
    {
        $lastValue = end($data)['total'] ?? 0;
        $avgGrowth = $trend['growth'] / 100;

        $prediction = [
            'tomorrow' => round($lastValue * (1 + $avgGrowth)),
            'this_week' => round($lastValue * (1 + $avgGrowth) * 7),
            'this_month' => round($lastValue * (1 + $avgGrowth) * 30),
            'next_week' => round($lastValue * (1 + $avgGrowth * 2) * 7),
            'next_month' => round($lastValue * (1 + $avgGrowth * 3) * 30),
        ];

        return $prediction;
    }

    private function getPredictionSummary(array $prediction): string
    {
        return "پیش‌بینی می‌شود که فردا حدود {$prediction['tomorrow']} نوبت داشته باشید. "
            . "در هفته آینده حدود {$prediction['next_week']} نوبت پیش‌بینی می‌شود.";
    }

    // ============================================================
    // 2. REVENUE FORECASTING - پیش‌بینی درآمد
    // ============================================================

    public function forecastRevenue(array $params = []): array
    {
        $months = $params['months'] ?? 6;

        // دریافت داده‌های تاریخی
        $historical = $this->getHistoricalRevenue($months);

        // محاسبه پیش‌بینی
        $forecast = $this->calculateRevenueForecast($historical);

        // ذخیره در دیتابیس
        $this->storeAnalytic('revenue_forecast', 'پیش‌بینی درآمد', [
            'historical' => $historical,
            'forecast' => $forecast,
            'params' => $params,
        ]);

        return [
            'historical' => $historical,
            'forecast' => $forecast,
            'summary' => $this->getRevenueSummary($forecast),
        ];
    }

    private function getHistoricalRevenue(int $months): array
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $revenue = Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('total_amount');

            $data[] = [
                'month' => $month->format('Y-m'),
                'revenue' => $revenue,
                'count' => Invoice::where('status', 'paid')
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->count(),
            ];
        }

        return $data;
    }

    private function calculateRevenueForecast(array $data): array
    {
        $total = array_sum(array_column($data, 'revenue'));
        $count = count($data);
        $avg = $count > 0 ? $total / $count : 0;

        // محاسبه رشد ماهانه
        $growth = 0;
        if ($count > 1) {
            $last = end($data)['revenue'];
            $first = reset($data)['revenue'];
            $growth = $first > 0 ? (($last - $first) / $first) * 100 : 0;
        }

        $monthlyGrowth = $growth / max(1, $count);

        $forecast = [];
        $current = $avg;
        for ($i = 1; $i <= 3; $i++) {
            $current = $current * (1 + ($monthlyGrowth / 100));
            $forecast[] = [
                'month' => Carbon::now()->addMonths($i)->format('Y-m'),
                'forecast' => round($current, 0),
            ];
        }

        return [
            'average_monthly' => round($avg, 0),
            'growth_rate' => round($growth, 1),
            'next_3_months' => $forecast,
            'total_next_quarter' => round(array_sum(array_column($forecast, 'forecast')), 0),
        ];
    }

    private function getRevenueSummary(array $forecast): string
    {
        return "میانگین درآمد ماهانه: " . number_format($forecast['average_monthly']) . " تومان. "
            . "پیش‌بینی درآمد سه ماه آینده: " . number_format($forecast['total_next_quarter']) . " تومان.";
    }

    // ============================================================
    // 3. PATIENT SEGMENTATION - بخش‌بندی بیماران
    // ============================================================

    public function segmentPatients(array $params = []): array
    {
        // دریافت همه بیماران با آمار
        $patients = Patient::withCount(['appointments'])
            ->withSum(['invoices as total_paid' => function ($q) {
                $q->where('status', 'paid');
            }])
            ->get();

        // بخش‌بندی بر اساس معیارهای مختلف
        $segments = [
            'by_visit_frequency' => $this->segmentByVisitFrequency($patients),
            'by_revenue' => $this->segmentByRevenue($patients),
            'by_age' => $this->segmentByAge($patients),
            'by_health_status' => $this->segmentByHealthStatus($patients),
        ];

        // بخش‌بندی ترکیبی
        $combined = $this->createCombinedSegments($patients);

        // ذخیره در دیتابیس
        $this->storeAnalytic('patient_segment', 'بخش‌بندی بیماران', [
            'segments' => $segments,
            'combined' => $combined,
            'total_patients' => $patients->count(),
            'params' => $params,
        ]);

        return [
            'segments' => $segments,
            'combined' => $combined,
            'summary' => $this->getSegmentSummary($segments),
        ];
    }

    private function segmentByVisitFrequency($patients): array
    {
        $high = $patients->filter(fn($p) => $p->appointments_count > 10);
        $medium = $patients->filter(fn($p) => $p->appointments_count >= 3 && $p->appointments_count <= 10);
        $low = $patients->filter(fn($p) => $p->appointments_count < 3 && $p->appointments_count > 0);
        $inactive = $patients->filter(fn($p) => $p->appointments_count == 0);

        return [
            'high_visit' => [
                'count' => $high->count(),
                'percentage' => $patients->count() > 0 ? round(($high->count() / $patients->count()) * 100, 1) : 0,
                'label' => 'پرمراجعه',
                'color' => '#22C55E',
            ],
            'medium_visit' => [
                'count' => $medium->count(),
                'percentage' => $patients->count() > 0 ? round(($medium->count() / $patients->count()) * 100, 1) : 0,
                'label' => 'متوسط',
                'color' => '#3B82F6',
            ],
            'low_visit' => [
                'count' => $low->count(),
                'percentage' => $patients->count() > 0 ? round(($low->count() / $patients->count()) * 100, 1) : 0,
                'label' => 'کم مراجعه',
                'color' => '#F59E0B',
            ],
            'inactive' => [
                'count' => $inactive->count(),
                'percentage' => $patients->count() > 0 ? round(($inactive->count() / $patients->count()) * 100, 1) : 0,
                'label' => 'غیرفعال',
                'color' => '#EF4444',
            ],
        ];
    }

    private function segmentByRevenue($patients): array
    {
        $high = $patients->filter(fn($p) => ($p->total_paid ?? 0) > 10000000);
        $medium = $patients->filter(fn($p) => ($p->total_paid ?? 0) >= 1000000 && ($p->total_paid ?? 0) <= 10000000);
        $low = $patients->filter(fn($p) => ($p->total_paid ?? 0) < 1000000);

        return [
            'high_value' => [
                'count' => $high->count(),
                'percentage' => $patients->count() > 0 ? round(($high->count() / $patients->count()) * 100, 1) : 0,
                'label' => 'ارزش بالا',
                'color' => '#8B5CF6',
            ],
            'medium_value' => [
                'count' => $medium->count(),
                'percentage' => $patients->count() > 0 ? round(($medium->count() / $patients->count()) * 100, 1) : 0,
                'label' => 'ارزش متوسط',
                'color' => '#06B6D4',
            ],
            'low_value' => [
                'count' => $low->count(),
                'percentage' => $patients->count() > 0 ? round(($low->count() / $patients->count()) * 100, 1) : 0,
                'label' => 'ارزش پایین',
                'color' => '#F59E0B',
            ],
        ];
    }

    private function segmentByAge($patients): array
    {
        // این بخش نیاز به فیلد تاریخ تولد در بیمار دارد
        return [
            'children' => ['count' => 0, 'percentage' => 0, 'label' => 'کودکان (زیر ۱۲ سال)'],
            'youth' => ['count' => 0, 'percentage' => 0, 'label' => 'جوانان (۱۲-۳۰ سال)'],
            'adults' => ['count' => 0, 'percentage' => 0, 'label' => 'بزرگسالان (۳۰-۶۰ سال)'],
            'elderly' => ['count' => 0, 'percentage' => 0, 'label' => 'سالمندان (بالای ۶۰ سال)'],
        ];
    }

    private function segmentByHealthStatus($patients): array
    {
        // این بخش نیاز به داده‌های سلامت بیماران دارد
        return [
            'healthy' => ['count' => 0, 'percentage' => 0, 'label' => 'سالم'],
            'chronic' => ['count' => 0, 'percentage' => 0, 'label' => 'مبتلا به بیماری مزمن'],
            'critical' => ['count' => 0, 'percentage' => 0, 'label' => 'وضعیت بحرانی'],
        ];
    }

    private function createCombinedSegments($patients): array
    {
        return [
            'vip_patients' => [
                'count' => $patients->filter(fn($p) => ($p->appointments_count > 5 && ($p->total_paid ?? 0) > 5000000))->count(),
                'label' => 'بیماران VIP',
                'description' => 'بیمارانی با مراجعه بالا و هزینه زیاد',
            ],
            'at_risk' => [
                'count' => $patients->filter(fn($p) => $p->appointments_count > 0 && ($p->appointments_count < 2))->count(),
                'label' => 'در معرض خطر',
                'description' => 'بیمارانی که ممکن است مراجعه را قطع کنند',
            ],
        ];
    }

    private function getSegmentSummary(array $segments): string
    {
        return "بیماران پرمراجعه: {$segments['by_visit_frequency']['high_visit']['count']} نفر. "
            . "بیماران با ارزش بالا: {$segments['by_revenue']['high_value']['count']} نفر.";
    }

    // ============================================================
    // 4. DOCTOR PERFORMANCE ANALYTICS - تحلیل عملکرد پزشکان
    // ============================================================

    public function analyzeDoctorPerformance(array $params = []): array
    {
        $doctorId = $params['doctor_id'] ?? null;
        $period = $params['period'] ?? 30; // days

        $doctors = Doctor::with(['user', 'specialty']);

        if ($doctorId) {
            $doctors->where('id', $doctorId);
        }

        $performance = [];
        foreach ($doctors->get() as $doctor) {
            $performance[] = $this->calculateDoctorMetrics($doctor, $period);
        }

        // رتبه‌بندی پزشکان
        $ranked = $this->rankDoctors($performance);

        // ذخیره در دیتابیس
        $this->storeAnalytic('doctor_performance', 'تحلیل عملکرد پزشکان', [
            'performance' => $performance,
            'ranked' => $ranked,
            'period' => $period,
            'params' => $params,
        ]);

        return [
            'doctors' => $performance,
            'ranked' => $ranked,
            'summary' => $this->getPerformanceSummary($ranked),
        ];
    }

    private function calculateDoctorMetrics($doctor, int $period): array
    {
        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('date', [Carbon::now()->subDays($period), Carbon::now()]);

        $completed = (clone $appointments)->where('status', 'completed');
        $cancelled = (clone $appointments)->where('status', 'cancelled');
        $noShow = (clone $appointments)->where('status', 'no_show');

        $total = $appointments->count();
        $completedCount = $completed->count();
        $cancelledCount = $cancelled->count();
        $noShowCount = $noShow->count();

        // درآمد
        $revenue = Invoice::whereHas('appointment', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })->where('status', 'paid')
            ->whereBetween('paid_at', [Carbon::now()->subDays($period), Carbon::now()])
            ->sum('total_amount');

        // امتیاز
        $rating = $doctor->rating ?? 0;
        $reviews = $doctor->total_reviews ?? 0;

        return [
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->full_name,
                'specialty' => $doctor->specialty?->name,
            ],
            'metrics' => [
                'total_appointments' => $total,
                'completed' => $completedCount,
                'cancelled' => $cancelledCount,
                'no_show' => $noShowCount,
                'completion_rate' => $total > 0 ? round(($completedCount / $total) * 100, 1) : 0,
                'cancellation_rate' => $total > 0 ? round(($cancelledCount / $total) * 100, 1) : 0,
                'no_show_rate' => $total > 0 ? round(($noShowCount / $total) * 100, 1) : 0,
                'average_patients_per_day' => $total > 0 ? round($total / $period, 1) : 0,
                'revenue' => $revenue,
                'average_revenue_per_patient' => $total > 0 ? round($revenue / $total, 0) : 0,
                'rating' => $rating,
                'total_reviews' => $reviews,
            ],
            'performance_score' => $this->calculatePerformanceScore($total, $completedCount, $revenue, $rating),
        ];
    }

    private function calculatePerformanceScore($total, $completed, $revenue, $rating): array
    {
        // امتیازدهی از ۰ تا ۱۰۰
        $score = 0;

        // تعداد نوبت (۳۰ امتیاز)
        if ($total >= 50) $score += 30;
        elseif ($total >= 30) $score += 25;
        elseif ($total >= 15) $score += 20;
        elseif ($total >= 5) $score += 10;

        // نرخ تکمیل (۳۰ امتیاز)
        $rate = $total > 0 ? ($completed / $total) : 0;
        if ($rate >= 0.9) $score += 30;
        elseif ($rate >= 0.75) $score += 25;
        elseif ($rate >= 0.6) $score += 20;
        elseif ($rate >= 0.4) $score += 10;

        // درآمد (۲۰ امتیاز)
        if ($revenue >= 50000000) $score += 20;
        elseif ($revenue >= 30000000) $score += 15;
        elseif ($revenue >= 10000000) $score += 10;
        elseif ($revenue >= 5000000) $score += 5;

        // امتیاز بیماران (۲۰ امتیاز)
        if ($rating >= 4.8) $score += 20;
        elseif ($rating >= 4.5) $score += 15;
        elseif ($rating >= 4.0) $score += 10;
        elseif ($rating >= 3.5) $score += 5;

        // تعیین سطح
        $level = match(true) {
            $score >= 85 => 'excellent',
            $score >= 70 => 'good',
            $score >= 50 => 'average',
            $score >= 30 => 'below_average',
            default => 'poor',
        };

        $levelLabels = [
            'excellent' => 'عالی',
            'good' => 'خوب',
            'average' => 'متوسط',
            'below_average' => 'زیر متوسط',
            'poor' => 'ضعیف',
        ];

        return [
            'score' => $score,
            'level' => $level,
            'level_label' => $levelLabels[$level],
            'color' => match($level) {
                'excellent' => '#22C55E',
                'good' => '#3B82F6',
                'average' => '#F59E0B',
                'below_average' => '#F97316',
                'poor' => '#EF4444',
            },
        ];
    }

    private function rankDoctors(array $performance): array
    {
        $ranked = $performance;
        usort($ranked, function ($a, $b) {
            return $b['performance_score']['score'] <=> $a['performance_score']['score'];
        });

        foreach ($ranked as $index => &$doctor) {
            $doctor['rank'] = $index + 1;
            $doctor['rank_label'] = $index == 0 ? '🥇' : ($index == 1 ? '🥈' : ($index == 2 ? '🥉' : "#{$index + 1}"));
        }

        return $ranked;
    }

    private function getPerformanceSummary(array $ranked): string
    {
        if (empty($ranked)) return 'هیچ داده‌ای برای تحلیل وجود ندارد.';

        $top = $ranked[0];
        return "بهترین پزشک: {$top['doctor']['name']} با امتیاز {$top['performance_score']['score']} "
            . "و سطح {$top['performance_score']['level_label']}.";
    }

    // ============================================================
    // 5. STORE ANALYTIC
    // ============================================================

    private function storeAnalytic(string $type, string $name, array $data): void
    {
        BIAnalytic::create([
            'type' => $type,
            'name' => $name,
            'data' => $data,
            'calculated_at' => now(),
        ]);
    }

    // ============================================================
    // 6. GET STORED ANALYTICS
    // ============================================================

    public function getAnalytics(string $type, int $limit = 10)
    {
        return BIAnalytic::byType($type)
            ->recent($limit)
            ->get();
    }

    public function getLatestAnalytic(string $type)
    {
        return BIAnalytic::byType($type)
            ->orderBy('calculated_at', 'desc')
            ->first();
    }
}
