<?php

namespace App\Services\AiChat\AI;

use App\Models\AiChat\AIPrompt;
use App\Enums\AiChat\MedicalCategory;
use App\Enums\AiChat\PromptCategory;
use App\Services\AiChat\System\ConfigManager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class PromptManager
{
    private array $defaultPrompts;
    private array $categoryMapping;

    public function __construct(private ConfigManager $configManager)
    {
        $this->defaultPrompts = $this->configManager->get('prompts.default_prompts', []);
        $this->categoryMapping = $this->configManager->get('prompts.category_mapping', []);
    }

    /**
     * دریافت پرامپت مناسب برای یک دسته‌بندی
     */
    public function getPromptForCategory(MedicalCategory $category): ?AIPrompt
    {
        $cacheKey = "prompt_category_{$category->value}";

        return Cache::remember($cacheKey, 3600, function () use ($category) {
            // ۱. جستجوی پرامپت فعال در دیتابیس
            $prompt = AIPrompt::where('is_active', true)
                ->where('category', $category->getPromptSlug())
                ->orderBy('priority', 'desc')
                ->first();

            if ($prompt) {
                return $prompt;
            }

            // ۲. بازگشت به پرامپت پیش‌فرض
            return $this->getDefaultPromptForCategory($category);
        });
    }

    /**
     * دریافت پرامپت پیش‌فرض برای دسته‌بندی
     */
    private function getDefaultPromptForCategory(MedicalCategory $category): ?AIPrompt
    {
        $slug = $category->getPromptSlug();
        $defaultData = $this->defaultPrompts[$slug] ?? $this->defaultPrompts['general'] ?? null;

        if (!$defaultData) {
            return null;
        }

        // ایجاد پرامپت مجازی (بدون ذخیره در دیتابیس)
        return new AIPrompt([
            'name' => "پیش‌فرض - " . $category->label(),
            'slug' => "default_{$slug}",
            'category' => $slug,
            'system_prompt' => $defaultData['system'] ?? '',
            'user_prompt_template' => $defaultData['user'] ?? '',
            'is_active' => true,
            'is_default' => true,
            'version' => 1,
        ]);
    }

    /**
     * دریافت پرامپت بر اساس اسلاگ
     */
    public function getPromptBySlug(string $slug): ?AIPrompt
    {
        return Cache::remember("prompt_{$slug}", 3600, function () use ($slug) {
            return AIPrompt::where('slug', $slug)->first();
        });
    }

    /**
     * کامپایل پرامپت با متغیرها
     */
    public function compilePrompt(AIPrompt $prompt, array $variables = []): string
    {
        $template = $prompt->user_prompt_template;
        return $this->replaceVariables($template, $variables);
    }

    /**
     * ساخت پرامپت کامل (سیستم + کاربر)
     */
    public function buildFullPrompt(MedicalCategory $category, array $variables = []): array
    {
        $prompt = $this->getPromptForCategory($category);

        if (!$prompt) {
            $prompt = $this->getDefaultPromptForCategory(MedicalCategory::GENERAL);
        }

        if (!$prompt) {
            return [
                'system' => 'شما یک دستیار پزشکی هستید. لطفاً به سوالات پزشکی پاسخ دهید.',
                'user' => $variables['question'] ?? $variables['message'] ?? 'سوال: ' . ($variables['message'] ?? ''),
            ];
        }

        // کامپایل پرامپت کاربر
        $userPrompt = $this->compilePrompt($prompt, $variables);

        // اضافه کردن متغیرهای ویژه
        $systemPrompt = $this->injectEmergencyInstructions(
            $prompt->system_prompt,
            $variables['severity'] ?? 'normal'
        );

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
            'prompt_id' => $prompt->id,
            'prompt_slug' => $prompt->slug,
            'category' => $category->value,
        ];
    }

    /**
     * تزریق دستورات اورژانسی به پرامپت سیستم
     */
    private function injectEmergencyInstructions(string $systemPrompt, string $severity): string
    {
        if ($severity === 'emergency') {
            $emergencyInstructions = "\n\n⚠️ **وضعیت اورژانسی تشخیص داده شده است!**\n"
                . "1. فوراً به کاربر بگویید با اورژانس (115) تماس بگیرد.\n"
                . "2. اقدامات اولیه ایمنی را راهنمایی کنید.\n"
                . "3. از هرگونه تشخیص قطعی خودداری کنید.\n"
                . "4. پاسخ شما باید با '⚠️ هشدار اورژانسی' شروع شود.\n";

            return $systemPrompt . $emergencyInstructions;
        }

        if ($severity === 'urgent') {
            $urgentInstructions = "\n\n⚠️ **وضعیت فوری تشخیص داده شده است!**\n"
                . "1. به کاربر توصیه کنید در اسرع وقت به پزشک مراجعه کند.\n"
                . "2. اقدامات موقت برای کاهش علائم را راهنمایی کنید.\n"
                . "3. تأکید کنید که این توصیه‌ها جایگزین تشخیص پزشکی نیست.\n";

            return $systemPrompt . $urgentInstructions;
        }

        return $systemPrompt;
    }

    /**
     * جایگزینی متغیرها
     */
    private function replaceVariables(string $template, array $variables): string
    {
        $search = [];
        $replace = [];

        foreach ($variables as $key => $value) {
            $search[] = '{' . $key . '}';
            $replace[] = is_array($value) ? implode('، ', $value) : (string) $value;
        }

        // متغیرهای پیش‌فرض
        $defaultVariables = [
            'time' => now()->format('Y/m/d H:i'),
            'date' => now()->format('Y/m/d'),
        ];

        foreach ($defaultVariables as $key => $value) {
            if (!isset($variables[$key])) {
                $search[] = '{' . $key . '}';
                $replace[] = $value;
            }
        }

        return str_replace($search, $replace, $template);
    }

    /**
     * ایجاد پرامپت جدید
     */
    public function createPrompt(array $data): AIPrompt
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        // اطمینان از یکتا بودن اسلاگ
        $slug = $data['slug'];
        $counter = 1;
        while (AIPrompt::where('slug', $slug)->exists()) {
            $slug = $data['slug'] . '-' . $counter++;
        }
        $data['slug'] = $slug;

        return AIPrompt::create($data);
    }

    /**
     * به‌روزرسانی پرامپت
     */
    public function updatePrompt(AIPrompt $prompt, array $data): AIPrompt
    {
        $prompt->update($data);
        Cache::forget("prompt_{$prompt->slug}");
        Cache::forget("prompt_category_{$prompt->category}");
        return $prompt->fresh();
    }

    /**
     * کلون کردن پرامپت (برای نسخه‌بندی)
     */
    public function clonePrompt(AIPrompt $prompt): AIPrompt
    {
        $clone = $prompt->replicate();
        $clone->slug = $prompt->slug . '-clone-' . time();
        $clone->version = $prompt->version + 1;
        $clone->is_active = false;
        $clone->is_default = false;
        $clone->usage_count = 0;
        $clone->save();

        return $clone;
    }

    /**
     * فعال/غیرفعال کردن پرامپت
     */
    public function togglePrompt(AIPrompt $prompt): bool
    {
        $prompt->is_active = !$prompt->is_active;
        $prompt->save();

        Cache::forget("prompt_{$prompt->slug}");
        Cache::forget("prompt_category_{$prompt->category}");

        return $prompt->is_active;
    }

    /**
     * ثبت استفاده از پرامپت
     */
    public function recordUsage(AIPrompt $prompt): void
    {
        $prompt->increment('usage_count');
        Cache::forget("prompt_{$prompt->slug}");
    }

    /**
     * دریافت پرامپت پیش‌فرض کلی
     */
    public function getDefaultPrompt(): ?AIPrompt
    {
        return Cache::remember('prompt_default', 3600, function () {
            return AIPrompt::where('is_default', true)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * دریافت همه پرامپت‌های فعال
     */
    public function getActivePrompts(): array
    {
        return AIPrompt::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * بازنشانی کش پرامپت‌ها
     */
    public function flushCache(): void
    {
        Cache::delete('prompt_default');
        foreach (MedicalCategory::cases() as $category) {
            Cache::delete("prompt_category_{$category->value}");
        }
    }
}
