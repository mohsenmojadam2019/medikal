<?php

namespace App\Services\Pharmacy;

use App\Models\PharmacyOrder;
use App\Models\PharmacyProduct;
use App\Models\Pharmacy;
use App\Models\InventoryLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PharmacyReportService
{
    /**
     * گزارش کلی داروخانه
     */
    public function getOverview(int $pharmacyId, array $dateRange = null): array
    {
        $query = PharmacyOrder::where('pharmacy_id', $pharmacyId);
        
        if ($dateRange) {
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }

        $totalOrders = $query->count();
        $totalRevenue = $query->sum('total_amount');
        $completedOrders = (clone $query)->where('status', 'delivered')->count();
        $cancelledOrders = (clone $query)->where('status', 'cancelled')->count();

        // فروش روزانه
        $dailySales = (clone $query)
            ->select(DB::raw('DATE(created_at) as date, SUM(total_amount) as total'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'completed_orders' => $completedOrders,
            'cancelled_orders' => $cancelledOrders,
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
            'daily_sales' => $dailySales,
            'date_range' => $dateRange,
        ];
    }

    /**
     * گزارش فروش محصولات
     */
    public function getProductSalesReport(int $pharmacyId, array $dateRange = null): array
    {
        $query = DB::table('pharmacy_orders')
            ->join('pharmacy_order_items', 'pharmacy_orders.id', '=', 'pharmacy_order_items.order_id')
            ->join('pharmacy_products', 'pharmacy_order_items.product_id', '=', 'pharmacy_products.id')
            ->where('pharmacy_orders.pharmacy_id', $pharmacyId)
            ->where('pharmacy_orders.status', 'delivered');

        if ($dateRange) {
            $query->whereBetween('pharmacy_orders.created_at', [$dateRange['start'], $dateRange['end']]);
        }

        $topProducts = $query
            ->select(
                'pharmacy_products.id',
                'pharmacy_products.name',
                DB::raw('SUM(pharmacy_order_items.quantity) as total_quantity'),
                DB::raw('SUM(pharmacy_order_items.total_price) as total_revenue')
            )
            ->groupBy('pharmacy_products.id', 'pharmacy_products.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();

        return [
            'top_products' => $topProducts,
            'total_products_sold' => $topProducts->sum('total_quantity'),
        ];
    }

    /**
     * گزارش موجودی
     */
    public function getInventoryReport(int $pharmacyId): array
    {
        $products = PharmacyProduct::where('pharmacy_id', $pharmacyId)->get();

        $totalProducts = $products->count();
        $lowStockProducts = $products->filter(function ($p) {
            return $p->stock <= $p->min_stock && $p->stock > 0;
        })->count();
        $outOfStockProducts = $products->filter(function ($p) {
            return $p->stock <= 0;
        })->count();
        $totalStockValue = $products->sum(function ($p) {
            return $p->stock * $p->price;
        });

        $recentInventoryLogs = InventoryLog::where('pharmacy_id', $pharmacyId)
            ->with(['product'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return [
            'total_products' => $totalProducts,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'total_stock_value' => $totalStockValue,
            'recent_logs' => $recentInventoryLogs,
            'summary' => [
                'excellent' => $products->filter(fn($p) => $p->stock > $p->min_stock * 2)->count(),
                'good' => $products->filter(fn($p) => $p->stock > $p->min_stock && $p->stock <= $p->min_stock * 2)->count(),
                'low' => $lowStockProducts,
                'out' => $outOfStockProducts,
            ],
        ];
    }

    /**
     * گزارش مالی
     */
    public function getFinancialReport(int $pharmacyId, array $dateRange = null): array
    {
        $query = PharmacyOrder::where('pharmacy_id', $pharmacyId);

        if ($dateRange) {
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }

        $totalRevenue = (clone $query)->sum('total_amount');
        $totalTax = (clone $query)->sum('tax');
        $totalDelivery = (clone $query)->sum('delivery_fee');
        $totalDiscount = (clone $query)->sum('discount');
        
        // پرداخت‌ها
        $paidOrders = (clone $query)->where('payment_status', 'paid')->count();
        $pendingPayments = (clone $query)->where('payment_status', 'pending')->count();

        // درآمد ماهانه
        $monthlyRevenue = (clone $query)
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total_amount) as total'))
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return [
            'total_revenue' => $totalRevenue,
            'total_tax' => $totalTax,
            'total_delivery_fee' => $totalDelivery,
            'total_discount' => $totalDiscount,
            'net_revenue' => $totalRevenue - $totalTax - $totalDelivery,
            'paid_orders' => $paidOrders,
            'pending_payments' => $pendingPayments,
            'monthly_revenue' => $monthlyRevenue,
        ];
    }

    /**
     * گزارش عملکرد روزانه
     */
    public function getDailyReport(int $pharmacyId, $date = null): array
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $orders = PharmacyOrder::where('pharmacy_id', $pharmacyId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('total_amount');
        $completedOrders = $orders->where('status', 'delivered')->count();

        // پیک‌ساعت فروش
        $hourlySales = $orders->groupBy(function ($order) {
            return $order->created_at->format('H:00');
        })->map(function ($group) {
            return [
                'orders' => $group->count(),
                'revenue' => $group->sum('total_amount'),
            ];
        });

        return [
            'date' => $date->format('Y-m-d'),
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'completed_orders' => $completedOrders,
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
            'hourly_sales' => $hourlySales,
            'top_products' => $this->getTopProductsForDay($pharmacyId, $date),
        ];
    }

    /**
     * پرفروش‌ترین محصولات روز
     */
    private function getTopProductsForDay(int $pharmacyId, Carbon $date): array
    {
        return DB::table('pharmacy_orders')
            ->join('pharmacy_order_items', 'pharmacy_orders.id', '=', 'pharmacy_order_items.order_id')
            ->join('pharmacy_products', 'pharmacy_order_items.product_id', '=', 'pharmacy_products.id')
            ->where('pharmacy_orders.pharmacy_id', $pharmacyId)
            ->whereDate('pharmacy_orders.created_at', $date)
            ->where('pharmacy_orders.status', 'delivered')
            ->select(
                'pharmacy_products.name',
                DB::raw('SUM(pharmacy_order_items.quantity) as quantity'),
                DB::raw('SUM(pharmacy_order_items.total_price) as revenue')
            )
            ->groupBy('pharmacy_products.name')
            ->orderBy('revenue', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }
}
