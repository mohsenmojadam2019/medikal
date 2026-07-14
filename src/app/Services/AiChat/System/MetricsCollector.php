<?php

namespace App\Services\AiChat\System;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MetricsCollector
{
    private array $metrics = [];
    private string $cacheKey = 'aichat_metrics';

    public function __construct()
    {
        $this->loadMetrics();
    }

    /**
     * ثبت رویداد
     */
    public function record(array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_numeric($value)) {
                $this->incrementCounter($key, $value);
            } else {
                $this->addToArray($key, $value);
            }
        }

        $this->saveMetrics();
    }

    /**
     * افزایش شمارنده
     */
    public function increment(string $key, int $amount = 1): void
    {
        $this->incrementCounter($key, $amount);
        $this->saveMetrics();
    }

    /**
     * ثبت زمان پاسخ‌دهی
     */
    public function recordResponseTime(int $milliseconds): void
    {
        $this->addToArray('response_times', $milliseconds);
        $this->saveMetrics();
    }

    /**
     * ثبت استفاده از مدل
     */
    public function recordModelUsage(string $model, int $tokens): void
    {
        $this->incrementCounter("model_{$model}_usage", 1);
        $this->incrementCounter("model_{$model}_tokens", $tokens);
        $this->saveMetrics();
    }

    /**
     * دریافت آمار کلی
     */
    public function getStats(): array
    {
        $this->loadMetrics();

        return [
            'total_messages' => $this->getCounter('messages_total'),
            'total_sessions' => $this->getCounter('sessions_total'),
            'total_tokens' => $this->getCounter('tokens_total'),
            'emergencies' => $this->getCounter('emergencies_detected'),
            'average_response_time' => $this->getAverage('response_times'),
            'feedback_helpful' => $this->getCounter('feedback_helpful'),
            'feedback_unhelpful' => $this->getCounter('feedback_unhelpful'),
            'model_stats' => $this->getModelStats(),
            'daily_stats' => $this->getDailyStats(),
            'hourly_stats' => $this->getHourlyStats(),
        ];
    }

    /**
     * افزایش شمارنده
     */
    private function incrementCounter(string $key, int $amount = 1): void
    {
        $this->metrics['counters'][$key] = ($this->metrics['counters'][$key] ?? 0) + $amount;
    }

    /**
     * دریافت مقدار شمارنده
     */
    private function getCounter(string $key): int
    {
        return $this->metrics['counters'][$key] ?? 0;
    }

    /**
     * اضافه کردن به آرایه
     */
    private function addToArray(string $key, $value): void
    {
        if (!isset($this->metrics['arrays'][$key])) {
            $this->metrics['arrays'][$key] = [];
        }
        $this->metrics['arrays'][$key][] = $value;

        // محدود کردن اندازه آرایه‌ها
        if (count($this->metrics['arrays'][$key]) > 1000) {
            array_shift($this->metrics['arrays'][$key]);
        }
    }

    /**
     * محاسبه میانگین
     */
    private function getAverage(string $key): ?float
    {
        $data = $this->metrics['arrays'][$key] ?? [];
        if (empty($data)) {
            return null;
        }
        return array_sum($data) / count($data);
    }

    /**
     * دریافت آمار مدل‌ها
     */
    private function getModelStats(): array
    {
        $stats = [];
        $prefix = 'model_';

        foreach ($this->metrics['counters'] as $key => $value) {
            if (str_starts_with($key, $prefix) && str_ends_with($key, '_usage')) {
                $model = str_replace([$prefix, '_usage'], '', $key);
                $tokens = $this->getCounter("{$prefix}{$model}_tokens");
                $stats[$model] = [
                    'usage' => $value,
                    'tokens' => $tokens,
                    'avg_tokens' => $value > 0 ? round($tokens / $value) : 0,
                ];
            }
        }

        return $stats;
    }

    /**
     * دریافت آمار روزانه
     */
    private function getDailyStats(): array
    {
        $stats = [];
        $cacheKey = "aichat_daily_{$this->getToday()}";
        $daily = Cache::get($cacheKey, ['messages' => 0, 'sessions' => 0]);

        $stats['today'] = $daily;

        // آمار هفت روز گذشته
        $weekStats = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $dayKey = "aichat_daily_{$date}";
            $dayData = Cache::get($dayKey, ['messages' => 0, 'sessions' => 0]);
            $weekStats[$date] = $dayData;
        }
        $stats['week'] = $weekStats;

        return $stats;
    }

    /**
     * دریافت آمار ساعتی
     */
    private function getHourlyStats(): array
    {
        $stats = [];
        for ($i = 0; $i < 24; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $stats[$hour . ':00'] = 0;
        }

        // پر کردن داده‌های واقعی
        foreach ($this->metrics['arrays']['hourly_activity'] ?? [] as $hour) {
            if (isset($stats[$hour])) {
                $stats[$hour]++;
            }
        }

        return $stats;
    }

    /**
     * دریافت تاریخ امروز
     */
    private function getToday(): string
    {
        return Carbon::now()->toDateString();
    }

    /**
     * بارگذاری متریک‌ها
     */
    private function loadMetrics(): void
    {
        $this->metrics = Cache::get($this->cacheKey, [
            'counters' => [],
            'arrays' => [],
        ]);
    }

    /**
     * ذخیره متریک‌ها
     */
    private function saveMetrics(): void
    {
        Cache::put($this->cacheKey, $this->metrics, 86400); // 24 ساعت

        // به‌روزرسانی آمار روزانه
        $today = $this->getToday();
        $dailyKey = "aichat_daily_{$today}";
        $daily = Cache::get($dailyKey, ['messages' => 0, 'sessions' => 0]);

        if ($this->getCounter('messages_total') > 0) {
            $daily['messages'] = $this->getCounter('messages_total');
        }
        if ($this->getCounter('sessions_total') > 0) {
            $daily['sessions'] = $this->getCounter('sessions_total');
        }

        Cache::put($dailyKey, $daily, 86400);

        // ثبت زمان فعلی برای آمار ساعتی
        $hour = now()->format('H:00');
        $this->addToArray('hourly_activity', $hour);
    }

    /**
     * بازنشانی متریک‌ها
     */
    public function reset(): void
    {
        $this->metrics = ['counters' => [], 'arrays' => []];
        Cache::forget($this->cacheKey);
        Log::info('Metrics reset');
    }

    /**
     * گرفتن کش
     */
    public function getCache(): array
    {
        return Cache::get($this->cacheKey, []);
    }
}
