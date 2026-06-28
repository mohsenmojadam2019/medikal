<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenantSlug = $request->header('X-Tenant') ?? $request->route('tenant');
        
        if (!$tenantSlug) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه کلینیک مشخص نشده است'
            ], 400);
        }

        $tenant = Tenant::where('slug', $tenantSlug)
            ->orWhere('subdomain', $tenantSlug)
            ->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'کلینیک یافت نشد'
            ], 404);
        }

        if (!$tenant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'این کلینیک غیرفعال است'
            ], 403);
        }

        $request->merge(['tenant' => $tenant]);
        $request->attributes->set('tenant', $tenant);
        session(['tenant_id' => $tenant->id]);

        return $next($request);
    }
}
