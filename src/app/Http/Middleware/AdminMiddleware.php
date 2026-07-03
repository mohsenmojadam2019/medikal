<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این بخش را ندارید'
            ], 403);
        }

        return $next($request);
    }
}
