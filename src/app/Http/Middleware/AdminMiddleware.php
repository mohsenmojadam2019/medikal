<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();

        // فقط کاربران با نقش admin یا super-admin به پنل مدیریت کامل دسترسی دارند
        if (!$user->hasRole(['admin', 'super-admin'])) {
            // کاربر عادی می‌تواند به پنل خودش دسترسی داشته باشد
            // اما به بخش‌های مدیریتی نه
            if ($request->routeIs('admin.users.*') ||
                $request->routeIs('admin.products.*') ||
                $request->routeIs('admin.orders.*') ||
                $request->routeIs('admin.roles.*') ||
                $request->routeIs('admin.permissions.*') ||
                $request->routeIs('admin.settings*')) {
                abort(403, 'شما دسترسی به این بخش ندارید.');
            }
        }

        return $next($request);
    }
}
