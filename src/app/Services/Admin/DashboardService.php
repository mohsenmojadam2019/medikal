<?php


namespace App\Services\Admin;

use App\Models\User;
//use App\Models\Product;
//use App\Models\Order;
use Carbon\Carbon;

class DashboardService
{
    /**
     * دریافت آمار داشبورد
     */
    public function getStats(): array
    {
        return [
            'total_users' => User::count(),
//            'total_products' => Product::count(),
//            'total_orders' => Order::count(),
//            'today_orders' => Order::whereDate('created_at', Carbon::today())->count(),
//            'pending_orders' => Order::where('status', 'pending')->count(),
//            'total_revenue' => Order::where('status', 'completed')->sum('total'),
//            'today_revenue' => Order::whereDate('created_at', Carbon::today())
//                ->where('status', 'completed')
//                ->sum('total'),
//            'recent_orders' => Order::with(['user', 'items'])
//                ->latest()
//                ->limit(10)
//                ->get(),
            'recent_users' => User::latest()->limit(10)->get(),
        ];
    }

    /**
     * دریافت داده‌های نمودار فروش
     */
//    public function getSalesChartData(int $days = 7): array
//    {
//        $labels = [];
//        $data = [];
//
//        for ($i = $days - 1; $i >= 0; $i--) {
//            $date = Carbon::now()->subDays($i);
//            $labels[] = $date->format('Y-m-d');
//            $data[] = Order::whereDate('created_at', $date)
//                ->where('status', 'completed')
//                ->sum('total');
//        }
//
//        return [
//            'labels' => $labels,
//            'data' => $data,
//        ];
//    }

    /**
     * دریافت آمار وضعیت سفارشات
     */
//    public function getOrderStatusStats(): array
//    {
//        $statuses = ['pending', 'processing', 'completed', 'cancelled'];
//        $stats = [];
//
//        foreach ($statuses as $status) {
//            $stats[$status] = Order::where('status', $status)->count();
//        }
//
//        return $stats;
//    }
}
