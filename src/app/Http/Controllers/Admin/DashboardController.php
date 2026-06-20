<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // اگر کاربر ادمین است، آمار کامل نمایش داده شود
        if ($user->hasRole(['admin', 'super-admin'])) {
            $stats = [
                'total_users' => \App\Models\User::count(),
//                'total_products' => \App\Models\Product::count(),
//                'total_orders' => \App\Models\Order::count(),
//                'pending_orders' => \App\Models\Order::where('status', 'pending')->count(),
//                'recent_orders' => \App\Models\Order::with('user')->latest()->limit(5)->get(),
            ];
            return view('admin.dashboard.admin', compact('stats', 'user'));
        }

        // اگر کاربر عادی است، فقط اطلاعات خودش نمایش داده شود
        $stats = [
            'total_orders' => $user->orders()->count(),
            'pending_orders' => $user->orders()->where('status', 'pending')->count(),
            'completed_orders' => $user->orders()->where('status', 'completed')->count(),
            'recent_orders' => $user->orders()->latest()->limit(5)->get(),
        ];

        return view('admin.dashboard.user', compact('stats', 'user'));
    }
}
