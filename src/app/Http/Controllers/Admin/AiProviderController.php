<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiChat\AIProvider;
use App\Services\AiChat\Gateway\AIProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class AiProviderController extends Controller
{
    public function __construct(
        private AIProviderManager $manager
    ) {}

    public function index(): JsonResponse
    {
        $providers = AIProvider::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn (AIProvider $provider) =>
                $this->resource($provider)
            );

        return $this->success($providers);
    }

    public function store(
        Request $request
    ): JsonResponse {
        $data = $this->validated($request);

        if (
            $data['driver'] !== 'ollama'
            && blank($data['api_key'] ?? null)
        ) {
            return $this->error(
                'کلید API الزامی است.',
                422
            );
        }

        $data['slug'] =
            $data['slug']
            ?? Str::slug($data['name']);

        if (blank($data['slug'])) {
            $data['slug'] =
                'provider-'.Str::lower(
                    Str::random(8)
                );
        }

        $provider = DB::transaction(
            function () use ($data) {
                if ($data['is_default'] ?? false) {
                    AIProvider::query()
                        ->update(['is_default' => false]);
                }

                return AIProvider::create($data);
            }
        );

        return $this->success(
            $this->resource($provider),
            'ارائه‌دهنده ایجاد شد.',
            201
        );
    }

    public function update(
        Request $request,
        AIProvider $provider
    ): JsonResponse {
        $data = $this->validated(
            $request,
            $provider
        );

        if (blank($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }

        DB::transaction(
            function () use ($provider, $data) {
                if ($data['is_default'] ?? false) {
                    AIProvider::query()
                        ->whereKeyNot($provider->id)
                        ->update(['is_default' => false]);
                }

                $provider->update($data);
            }
        );

        return $this->success(
            $this->resource($provider->fresh()),
            'ارائه‌دهنده به‌روزرسانی شد.'
        );
    }

    public function destroy(
        AIProvider $provider
    ): JsonResponse {
        if ($provider->is_default) {
            return $this->error(
                'ابتدا یک ارائه‌دهنده دیگر را پیش‌فرض کنید.',
                422
            );
        }

        $provider->delete();

        return $this->success(
            null,
            'ارائه‌دهنده حذف شد.'
        );
    }

    public function setDefault(
        AIProvider $provider
    ): JsonResponse {
        if (!$provider->is_active) {
            return $this->error(
                'ارائه‌دهنده غیرفعال را نمی‌توان پیش‌فرض کرد.',
                422
            );
        }

        DB::transaction(
            function () use ($provider) {
                AIProvider::query()
                    ->update(['is_default' => false]);

                $provider->update([
                    'is_default' => true,
                ]);
            }
        );

        return $this->success(
            $this->resource($provider->fresh()),
            'ارائه‌دهنده پیش‌فرض شد.'
        );
    }

    public function test(
        Request $request,
        AIProvider $provider
    ): JsonResponse {
        $data = $request->validate([
            'prompt' =>
                'nullable|string|max:2000',
        ]);

        try {
            $startedAt = microtime(true);

            $result = $this->manager->test(
                $provider,
                $data['prompt']
                    ?? 'فقط بنویس: اتصال با موفقیت برقرار شد.'
            );

            $duration = (int) round(
                (microtime(true) - $startedAt) * 1000
            );

            $provider->update([
                'last_tested_at' => now(),
                'last_test_success' => true,
                'last_test_message' =>
                    'پاسخ در '.$duration.' میلی‌ثانیه',
            ]);

            return $this->success([
                'provider' =>
                    $this->resource($provider->fresh()),
                'response' => $result['text'],
                'model' => $result['model'],
                'usage' => $result['usage'],
                'duration_ms' => $duration,
            ], 'تست اتصال موفق بود.');
        } catch (Throwable $exception) {
            report($exception);

            $provider->update([
                'last_tested_at' => now(),
                'last_test_success' => false,
                'last_test_message' =>
                    mb_substr(
                        $exception->getMessage(),
                        0,
                        1000
                    ),
            ]);

            return $this->error(
                $exception->getMessage(),
                422
            );
        }
    }

    public function models(
        AIProvider $provider
    ): JsonResponse {
        try {
            return $this->success(
                $this->manager->models($provider)
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                $exception->getMessage(),
                422
            );
        }
    }

    public function chatTest(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'provider_id' =>
                'nullable|integer|exists:ai_providers,id',
            'message' =>
                'required|string|max:5000',
            'system_prompt' =>
                'nullable|string|max:10000',
            'model' =>
                'nullable|string|max:255',
            'temperature' =>
                'nullable|numeric|min:0|max:2',
            'max_tokens' =>
                'nullable|integer|min:1|max:32000',
        ]);

        $provider = filled($data['provider_id'] ?? null)
            ? AIProvider::findOrFail(
                $data['provider_id']
            )
            : $this->manager->defaultProvider();

        try {
            $messages = [];

            if (filled($data['system_prompt'] ?? null)) {
                $messages[] = [
                    'role' => 'system',
                    'content' =>
                        $data['system_prompt'],
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $data['message'],
            ];

            $startedAt = microtime(true);

            $result = $this->manager->generate(
                $messages,
                $provider,
                [
                    'model' =>
                        $data['model'] ?? null,
                    'temperature' =>
                        $data['temperature'] ?? null,
                    'max_tokens' =>
                        $data['max_tokens'] ?? 700,
                    'max_output_tokens' =>
                        $data['max_tokens'] ?? 700,
                ]
            );

            return $this->success([
                'response' => $result['text'],
                'provider' => $provider->slug,
                'model' => $result['model'],
                'usage' => $result['usage'],
                'duration_ms' => (int) round(
                    (microtime(true) - $startedAt)
                    * 1000
                ),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                $exception->getMessage(),
                422
            );
        }
    }

    private function validated(
        Request $request,
        ?AIProvider $provider = null
    ): array {
        return $request->validate([
            'name' => 'required|string|max:120',
            'slug' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique(
                    'ai_providers',
                    'slug'
                )->ignore($provider?->id),
            ],
            'driver' => [
                'required',
                Rule::in([
                    'openai',
                    'openai_compatible',
                    'ollama',
                ]),
            ],
            'base_url' =>
                'nullable|url|max:500',
            'api_key' =>
                'nullable|string|max:2000',
            'organization' =>
                'nullable|string|max:255',
            'project' =>
                'nullable|string|max:255',
            'default_model' =>
                'required|string|max:255',
            'models' =>
                'nullable|array',
            'options' =>
                'nullable|array',
            'is_active' =>
                'required|boolean',
            'is_default' =>
                'required|boolean',
        ]);
    }

    private function resource(
        AIProvider $provider
    ): array {
        return [
            'id' => $provider->id,
            'name' => $provider->name,
            'slug' => $provider->slug,
            'driver' => $provider->driver,
            'base_url' => $provider->base_url,
            'organization' => $provider->organization,
            'project' => $provider->project,
            'default_model' =>
                $provider->default_model,
            'models' => $provider->models ?? [],
            'options' => $provider->options ?? [],
            'is_active' => $provider->is_active,
            'is_default' => $provider->is_default,
            'has_api_key' => $provider->has_api_key,
            'last_tested_at' =>
                $provider->last_tested_at
                    ?->toISOString(),
            'last_test_success' =>
                $provider->last_test_success,
            'last_test_message' =>
                $provider->last_test_message,
            'created_at' =>
                $provider->created_at?->toISOString(),
            'updated_at' =>
                $provider->updated_at?->toISOString(),
        ];
    }

    private function success(
        mixed $data = null,
        string $message = 'عملیات موفق بود.',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function error(
        string $message,
        int $status = 400,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
