<?php

namespace App\Services\System;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class SystemService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function getLogFiles(): array
    {
        $logPath = storage_path('logs');
        $files = File::files($logPath);
        $logFiles = [];

        foreach ($files as $file) {
            $logFiles[] = [
                'name' => $file->getFilename(),
                'size' => $this->formatFileSize($file->getSize()),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                'path' => $file->getPathname(),
            ];
        }

        usort($logFiles, function ($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });

        return $logFiles;
    }

    public function getLogContent(string $filename, int $lines = 100): string
    {
        $logPath = storage_path('logs/' . $filename);
        if (!File::exists($logPath)) {
            throw new \Exception('فایل لاگ یافت نشد');
        }

        return File::get($logPath, $lines);
    }

    public function deleteLogFile(string $filename): bool
    {
        $logPath = storage_path('logs/' . $filename);
        if (!File::exists($logPath)) {
            throw new \Exception('فایل لاگ یافت نشد');
        }

        return File::delete($logPath);
    }

    public function clearAllLogs(): int
    {
        $logPath = storage_path('logs');
        $files = File::files($logPath);
        $count = 0;

        foreach ($files as $file) {
            File::delete($file);
            $count++;
        }

        return $count;
    }

    public function clearAllCache(): array
    {
        $results = [];

        try {
            Artisan::call('cache:clear');
            $results['cache'] = '✅ کش اپلیکیشن پاک شد';

            Artisan::call('config:clear');
            $results['config'] = '✅ کش کانفیگ پاک شد';

            Artisan::call('route:clear');
            $results['route'] = '✅ کش رووت پاک شد';

            Artisan::call('view:clear');
            $results['view'] = '✅ کش ویو پاک شد';

            Artisan::call('optimize:clear');
            $results['optimize'] = '✅ کش آپتیمایز پاک شد';

            if (function_exists('opcache_reset')) {
                opcache_reset();
                $results['opcache'] = '✅ کش اوپکد پاک شد';
            }

            Cache::flush();
            $results['cache_store'] = '✅ کش استور پاک شد';

            $results['status'] = 'success';
            $results['tenant_id'] = $this->tenantId;

        } catch (\Exception $e) {
            $results['error'] = '❌ خطا: ' . $e->getMessage();
            $results['status'] = 'error';
        }

        return $results;
    }

    public function getSystemInfo(): array
    {
        return [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_usage' => $this->formatFileSize(memory_get_usage(true)),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'tenant_id' => $this->tenantId,
        ];
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
