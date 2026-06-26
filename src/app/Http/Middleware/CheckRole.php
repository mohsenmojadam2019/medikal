<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت نشده‌اید'
            ], 401);
        }

        // اگر کاربر super_admin باشد، به همه چیز دسترسی دارد
        if ($request->user()->hasRole('super_admin')) {
            return $next($request);
        }

        if (!$request->user()->hasAnyRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این بخش را ندارید'
            ], 403);
        }

        return $next($request);
    }
}
