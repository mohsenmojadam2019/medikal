<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * نمایش تنظیمات
     */
    public function index()
    {
        $settings = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'timezone' => config('app.timezone'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * بروزرسانی تنظیمات
     */
    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'timezone' => 'required|string',
            'app_debug' => 'sometimes|boolean',
        ]);

        // ذخیره در فایل .env
        $this->updateEnv([
            'APP_NAME' => $request->app_name,
            'APP_URL' => $request->app_url,
            'APP_TIMEZONE' => $request->timezone,
            'APP_DEBUG' => $request->app_debug ? 'true' : 'false',
        ]);

        // پاک کردن کش
        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        return redirect()->route('admin.settings')
            ->with('success', 'تنظیمات با موفقیت بروزرسانی شد.');
    }

    /**
     * پاک کردن کش سیستم
     */
    public function clearCache()
    {
        Artisan::call('optimize:clear');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        return redirect()->route('admin.settings')
            ->with('success', 'کش سیستم با موفقیت پاک شد.');
    }

    /**
     * بروزرسانی فایل .env
     */
    private function updateEnv(array $data)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                $content .= "\n{$replacement}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
