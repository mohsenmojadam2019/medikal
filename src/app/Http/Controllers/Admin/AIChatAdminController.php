<?php
// app/Http/Controllers/Admin/AIChatAdminController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiChat\AIChatService;
use App\Services\AiChat\AI\PromptManager;
use App\Services\AiChat\System\ConfigManager;
use App\Services\AiChat\System\DataCleanupService;
use App\Services\AiChat\System\MetricsCollector;
use App\Models\AiChat\AIPrompt;
use App\Models\AiChat\ChatSession;
use App\Models\AiChat\MedicalQuery;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AIChatAdminController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AIChatService $chatService,
        private PromptManager $promptManager,
        private ConfigManager $configManager,
        private DataCleanupService $cleanupService,
        private MetricsCollector $metricsCollector
    ) {}

    // ============================================================
    // PROMPT MANAGEMENT
    // ============================================================

    public function prompts(Request $request)
    {
        try {
            $query = AIPrompt::query();

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->has('search')) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('slug', 'like', "%{$request->search}%");
            }

            $prompts = $query->orderBy('priority', 'desc')
                ->paginate($request->get('per_page', 20));

            return $this->success($prompts);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function storePrompt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'category' => 'required|string|in:general,medical,pharmacy,emergency,nutrition,psychology',
            'system_prompt' => 'required|string',
            'user_prompt_template' => 'required|string',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $prompt = $this->promptManager->createPrompt($data);
            return $this->success($prompt, 'پرامپت با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updatePrompt(Request $request, $id)
    {
        try {
            $prompt = AIPrompt::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('پرامپت یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'category' => 'sometimes|string|in:general,medical,pharmacy,emergency,nutrition,psychology',
            'system_prompt' => 'sometimes|string',
            'user_prompt_template' => 'sometimes|string',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $prompt = $this->promptManager->updatePrompt($prompt, $request->all());
            return $this->success($prompt, 'پرامپت با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deletePrompt($id)
    {
        try {
            $prompt = AIPrompt::findOrFail($id);
            $prompt->delete();
            return $this->success(null, 'پرامپت با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function togglePrompt($id)
    {
        try {
            $prompt = AIPrompt::findOrFail($id);
            $this->promptManager->togglePrompt($prompt);
            return $this->success($prompt->fresh(), 'وضعیت پرامپت با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // SETTINGS
    // ============================================================

    public function settings()
    {
        try {
            $settings = [
                'provider' => [
                    'default' => $this->chatService->getDefaultProvider(),
                    'available' => $this->chatService->getActiveProviders(),
                ],
                'session' => [
                    'lifetime' => $this->configManager->get('session.lifetime', 1440),
                    'auto_cleanup' => $this->configManager->get('session.auto_cleanup', true),
                    'cleanup_days' => $this->configManager->get('session.cleanup_days', 1),
                ],
                'models' => [
                    'default' => $this->configManager->get('models.default', 'qwen3:14b'),
                    'fallback' => $this->configManager->get('models.fallback', 'llama3.1'),
                    'emergency' => $this->configManager->get('models.emergency', 'qwen3:14b'),
                ],
                'filter' => [
                    'enabled' => $this->configManager->get('filter.enabled', true),
                    'strict' => $this->configManager->get('filter.strict', true),
                ],
                'emergency' => [
                    'enabled' => $this->configManager->get('emergency.enabled', true),
                    'phone' => $this->configManager->get('emergency.phone', '115'),
                ],
                'rate_limit' => [
                    'per_minute' => $this->configManager->get('rate_limit.per_minute', 10),
                    'per_hour' => $this->configManager->get('rate_limit.per_hour', 100),
                    'per_day' => $this->configManager->get('rate_limit.per_day', 500),
                ],
                'ollama' => [
                    'url' => $this->configManager->get('ollama.url', 'http://host.docker.internal:11434'),
                    'timeout' => $this->configManager->get('ollama.timeout', 60),
                    'max_retries' => $this->configManager->get('ollama.max_retries', 3),
                    'options' => $this->configManager->get('ollama.options', []),
                ],
                'openai' => [
                    'api_key' => $this->configManager->get('openai.api_key') ? '***' : null,
                    'model' => $this->configManager->get('openai.model', 'gpt-4o-mini'),
                ],
                'gemini' => [
                    'api_key' => $this->configManager->get('gemini.api_key') ? '***' : null,
                    'model' => $this->configManager->get('gemini.model', 'gemini-1.5-pro'),
                ],
            ];

            return $this->success($settings);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider.default' => 'nullable|string|in:ollama,openai,gemini',
            'session.lifetime' => 'nullable|integer|min:1|max:10080',
            'session.auto_cleanup' => 'nullable|boolean',
            'session.cleanup_days' => 'nullable|integer|min:1|max:30',
            'models.default' => 'nullable|string',
            'models.fallback' => 'nullable|string',
            'models.emergency' => 'nullable|string',
            'filter.enabled' => 'nullable|boolean',
            'filter.strict' => 'nullable|boolean',
            'emergency.enabled' => 'nullable|boolean',
            'emergency.phone' => 'nullable|string|max:20',
            'rate_limit.per_minute' => 'nullable|integer|min:1|max:100',
            'rate_limit.per_hour' => 'nullable|integer|min:1|max:1000',
            'rate_limit.per_day' => 'nullable|integer|min:1|max:5000',
            'ollama.url' => 'nullable|string|url',
            'ollama.timeout' => 'nullable|integer|min:1|max:300',
            'ollama.max_retries' => 'nullable|integer|min:1|max:10',
            'ollama.options.temperature' => 'nullable|numeric|min:0|max:2',
            'ollama.options.max_tokens' => 'nullable|integer|min:1|max:4096',
            'openai.model' => 'nullable|string',
            'gemini.model' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            foreach ($request->all() as $key => $value) {
                $this->configManager->set($key, $value);
            }

            // اگر provider پیش‌فرض تغییر کرده
            if ($request->has('provider.default')) {
                $this->chatService->setDefaultProvider($request->input('provider.default'));
            }

            return $this->success(null, 'تنظیمات با موفقیت ذخیره شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // MODELS
    // ============================================================

    public function models(Request $request)
    {
        try {
            $provider = $request->get('provider', 'ollama');
            $providerInstance = $this->chatService->providerFactory->make($provider);

            return $this->success([
                'provider' => $provider,
                'models' => $providerInstance->listModels(),
                'current' => $providerInstance->getModel(),
                'available' => $providerInstance->isAvailable(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function testModel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'nullable|string|in:ollama,openai,gemini',
            'model' => 'nullable|string',
            'prompt' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $provider = $this->chatService->providerFactory->make($request->provider);
            $provider->setModel($request->model ?? $provider->getModel());

            $prompt = $request->prompt ?? 'سلام، لطفاً یک پیام آزمایشی بنویسید.';
            $response = $provider->generate($prompt);

            return $this->success([
                'provider' => $provider->getProviderName(),
                'model' => $provider->getModel(),
                'response' => $response,
                'tokens_used' => $provider->getLastTokensUsed(),
                'confidence' => $provider->getLastConfidence(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // ANALYTICS
    // ============================================================

    public function analytics()
    {
        try {
            $stats = $this->metricsCollector->getStats();
            $medicalStats = $this->medicalFilter->getStatistics();

            return $this->success(array_merge($stats, [
                'medical' => $medicalStats,
                'session_stats' => DataCleanupService::getStats(),
            ]));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function queries(Request $request)
    {
        try {
            $query = MedicalQuery::with(['user', 'session'])
                ->orderBy('created_at', 'desc');

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('severity')) {
                $query->where('severity', $request->severity);
            }

            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $queries = $query->paginate($request->get('per_page', 20));

            return $this->success($queries);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function exportAnalytics(Request $request)
    {
        try {
            // خروجی CSV/Excel
            return $this->success([
                'message' => 'خروجی در حال ساخت است...',
                'url' => route('admin.ai.analytics.download'),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ============================================================
    // CLEANUP
    // ============================================================

    public function cleanup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:30',
            'dry_run' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            if ($request->dry_run) {
                $result = $this->cleanupService->dryRun($request->days);
                return $this->success($result, 'نتایج بررسی پاکسازی');
            }

            $result = $this->cleanupService->runCleanup($request->days);
            return $this->success($result, 'پاکسازی با موفقیت انجام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
