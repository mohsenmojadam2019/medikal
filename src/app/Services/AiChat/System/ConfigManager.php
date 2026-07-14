<?php

namespace App\Services\AiChat\System;

use App\Models\AiChat\ChatConfig;
use Illuminate\Support\Facades\Cache;

class ConfigManager
{
    private array $cache = [];

    /**
     * دریافت تنظیمات
     */
    public function get(string $key, $default = null)
    {
        // ۱. بررسی کش
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        // ۲. جستجو در دیتابیس
        $config = ChatConfig::where('key', $key)->first();

        if ($config) {
            $value = $this->castValue($config->value, $config->type);
            $this->cache[$key] = $value;
            return $value;
        }

        // ۳. بازگشت به مقدار پیش‌فرض
        return $this->getDefaultValue($key, $default);
    }

    /**
     * دریافت تنظیمات به صورت بولین
     */
    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    /**
     * دریافت تنظیمات به صورت عددی
     */
    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * دریافت تنظیمات به صورت آرایه
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);
        return is_array($value) ? $value : $default;
    }

    /**
     * تنظیم مقدار
     */
    public function set(string $key, $value, string $type = 'string'): bool
    {
        ChatConfig::updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->prepareValue($value),
                'type' => $type,
            ]
        );

        $this->cache[$key] = $value;
        Cache::forget("config_{$key}");

        return true;
    }

    /**
     * حذف تنظیمات
     */
    public function delete(string $key): bool
    {
        ChatConfig::where('key', $key)->delete();
        unset($this->cache[$key]);
        Cache::forget("config_{$key}");
        return true;
    }

    /**
     * ریست کردن کش تنظیمات
     */
    public function flushCache(): void
    {
        $this->cache = [];
        ChatConfig::all()->each(function ($config) {
            Cache::forget("config_{$config->key}");
        });
    }

    /**
     * دریافت تمام تنظیمات
     */
    public function all(): array
    {
        return ChatConfig::all()
            ->map(function ($config) {
                return [
                    'key' => $config->key,
                    'value' => $this->castValue($config->value, $config->type),
                    'type' => $config->type,
                    'category' => $config->category,
                    'description' => $config->description,
                ];
            })
            ->toArray();
    }

    /**
     * دریافت تنظیمات بر اساس دسته‌بندی
     */
    public function getByCategory(string $category): array
    {
        return ChatConfig::where('category', $category)
            ->get()
            ->map(function ($config) {
                return [
                    'key' => $config->key,
                    'value' => $this->castValue($config->value, $config->type),
                    'type' => $config->type,
                ];
            })
            ->toArray();
    }

    /**
     * بازنشانی به تنظیمات پیش‌فرض
     */
    public function resetToDefaults(): void
    {
        $defaults = $this->getDefaultConfigs();

        foreach ($defaults as $key => $config) {
            ChatConfig::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $this->prepareValue($config['value']),
                    'type' => $config['type'],
                    'category' => $config['category'],
                    'description' => $config['description'],
                ]
            );
        }

        $this->flushCache();
    }

    /**
     * دریافت مقدار پیش‌فرض
     */
    private function getDefaultValue(string $key, $default = null)
    {
        $defaults = $this->getDefaultConfigs();
        return $defaults[$key]['value'] ?? $default;
    }

    /**
     * دریافت تنظیمات پیش‌فرض
     */
    private function getDefaultConfigs(): array
    {
        return [
            'session.lifetime' => [
                'value' => 1440,
                'type' => 'integer',
                'category' => 'session',
                'description' => 'مدت زمان اعتبار جلسه به دقیقه',
            ],
            'session.auto_cleanup' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'session',
                'description' => 'فعال/غیرفعال کردن پاکسازی خودکار',
            ],
            'session.cleanup_days' => [
                'value' => 1,
                'type' => 'integer',
                'category' => 'session',
                'description' => 'تعداد روزهای نگهداری داده',
            ],
            'models.default' => [
                'value' => 'qwen3:14b',
                'type' => 'string',
                'category' => 'models',
                'description' => 'مدل پیش‌فرض',
            ],
            'models.fallback' => [
                'value' => 'llama3.1',
                'type' => 'string',
                'category' => 'models',
                'description' => 'مدل جایگزین',
            ],
            'filter.enabled' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'filter',
                'description' => 'فعال بودن فیلتر پزشکی',
            ],
            'filter.strict' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'filter',
                'description' => 'حالت دقیق فیلتر (فقط پزشکی)',
            ],
            'emergency.enabled' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'emergency',
                'description' => 'فعال بودن تشخیص اورژانس',
            ],
            'emergency.phone' => [
                'value' => '115',
                'type' => 'string',
                'category' => 'emergency',
                'description' => 'شماره اورژانس',
            ],
            'file.max_size' => [
                'value' => 5120,
                'type' => 'integer',
                'category' => 'file',
                'description' => 'حداکثر حجم فایل بر حسب کیلوبایت',
            ],
            'rate_limit.per_minute' => [
                'value' => 10,
                'type' => 'integer',
                'category' => 'rate_limit',
                'description' => 'حداکثر درخواست در دقیقه',
            ],
            'rate_limit.per_hour' => [
                'value' => 100,
                'type' => 'integer',
                'category' => 'rate_limit',
                'description' => 'حداکثر درخواست در ساعت',
            ],
            'rate_limit.per_day' => [
                'value' => 500,
                'type' => 'integer',
                'category' => 'rate_limit',
                'description' => 'حداکثر درخواست در روز',
            ],
            'ollama.url' => [
                'value' => 'http://host.docker.internal:11434',
                'type' => 'string',
                'category' => 'ollama',
                'description' => 'آدرس سرور Ollama',
            ],
            'ollama.timeout' => [
                'value' => 60,
                'type' => 'integer',
                'category' => 'ollama',
                'description' => 'زمان تایم‌اوت به ثانیه',
            ],
            'ollama.max_retries' => [
                'value' => 3,
                'type' => 'integer',
                'category' => 'ollama',
                'description' => 'تعداد تلاش مجدد',
            ],
            'ollama.options.temperature' => [
                'value' => 0.7,
                'type' => 'float',
                'category' => 'ollama',
                'description' => 'دمای خلاقیت مدل',
            ],
            'ollama.options.max_tokens' => [
                'value' => 500,
                'type' => 'integer',
                'category' => 'ollama',
                'description' => 'حداکثر توکن‌های خروجی',
            ],
        ];
    }

    /**
     * تبدیل مقدار به نوع مناسب
     */
    private function castValue(string $value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'float' => (float) $value,
            'json' => json_decode($value, true),
            'array' => json_decode($value, true) ?? explode(',', $value),
            default => $value,
        };
    }

    /**
     * آماده‌سازی مقدار برای ذخیره
     */
    private function prepareValue($value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return (string) $value;
    }
}
