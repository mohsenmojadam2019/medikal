<?php


namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // ============================================
            // ✅ روت‌های وب (عمومی)
            // ============================================
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // ============================================
            // ✅ روت‌های API
            // ============================================
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // ============================================
            // ✅ روت‌های ادمین (جداسازی شده)
            // ============================================
            Route::middleware('web')
                ->prefix('admin')
                ->group(base_path('routes/admin.php'));

            // ============================================
            // ✅ روت‌های WebSocket (Reverb)
            // ============================================
            Route::middleware('web')
                ->group(base_path('routes/channels.php'));
        });
    }
}
