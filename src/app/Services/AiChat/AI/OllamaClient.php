<?php

namespace App\Services\AiChat\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AiChat\System\ConfigManager;
use App\Exceptions\AiChat\ModelNotFoundException;

class OllamaClient
{
    private string $baseUrl;
    private string $model;
    private string $systemPrompt = '';
    private array $options = [];
    private ?int $lastTokensUsed = null;
    private ?float $lastConfidence = null;
    private float $timeout = 60;
    private int $maxRetries = 3;

    public function __construct(private ConfigManager $configManager)
    {
        $this->baseUrl = $this->configManager->get('ollama.url', 'http://host.docker.internal:11434');
        $this->model = $this->configManager->get('ollama.model', 'qwen3:14b');
        $this->timeout = $this->configManager->get('ollama.timeout', 60);
        $this->maxRetries = $this->configManager->get('ollama.max_retries', 3);
        $this->options = $this->configManager->get('ollama.options', [
            'temperature' => 0.7,
            'top_p' => 0.9,
            'max_tokens' => 500,
        ]);
    }

    /**
     * تنظیم مدل
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * تنظیم پرامپت سیستم
     */
    public function setSystemPrompt(string $prompt): self
    {
        $this->systemPrompt = $prompt;
        return $this;
    }

    /**
     * تنظیم پارامترها
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * تنظیم زمان تایم‌اوت
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * دریافت مدل فعلی
     */
    public function getCurrentModel(): string
    {
        return $this->model;
    }

    /**
     * دریافت آخرین توکن‌های مصرفی
     */
    public function getLastTokensUsed(): ?int
    {
        return $this->lastTokensUsed;
    }

    /**
     * دریافت آخرین امتیاز اطمینان
     */
    public function getLastConfidence(): ?float
    {
        return $this->lastConfidence;
    }

    /**
     * تولید پاسخ
     */
    public function generate(string $prompt): string
    {
        $this->lastTokensUsed = null;
        $this->lastConfidence = null;

        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => $this->options,
        ];

        if (!empty($this->systemPrompt)) {
            $payload['system'] = $this->systemPrompt;
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout($this->timeout)
                    ->post($this->baseUrl . '/api/generate', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $this->lastTokensUsed = $data['eval_count'] ?? null;
                    $this->lastConfidence = $this->calculateConfidence($data['response'] ?? '');
                    return $data['response'] ?? '';
                }

                throw new \Exception('Ollama API error: ' . $response->status());
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                Log::warning("Ollama attempt {$attempt} failed", [
                    'model' => $this->model,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $this->maxRetries) {
                    sleep(1); // تأخیر قبل از تلاش مجدد
                }
            }
        }

        Log::error('Ollama failed after retries', [
            'model' => $this->model,
            'error' => $lastException?->getMessage(),
        ]);

        throw new ModelNotFoundException("Model '{$this->model}' unavailable after {$this->maxRetries} attempts");
    }

    /**
     * تولید پاسخ با تاریخچه مکالمه
     */
    public function chat(array $messages): string
    {
        $this->lastTokensUsed = null;
        $this->lastConfidence = null;

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => false,
            'options' => $this->options,
        ];

        if (!empty($this->systemPrompt)) {
            array_unshift($payload['messages'], [
                'role' => 'system',
                'content' => $this->systemPrompt,
            ]);
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout($this->timeout)
                    ->post($this->baseUrl . '/api/chat', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $this->lastTokensUsed = $data['eval_count'] ?? null;
                    $this->lastConfidence = $this->calculateConfidence($data['message']['content'] ?? '');
                    return $data['message']['content'] ?? '';
                }

                throw new \Exception('Ollama API chat error: ' . $response->status());
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                Log::warning("Ollama chat attempt {$attempt} failed", [
                    'model' => $this->model,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $this->maxRetries) {
                    sleep(1);
                }
            }
        }

        Log::error('Ollama chat failed after retries', [
            'model' => $this->model,
            'error' => $lastException?->getMessage(),
        ]);

        throw new ModelNotFoundException("Model '{$this->model}' unavailable for chat");
    }

    /**
     * تولید پاسخ به صورت جریانی
     */
    public function stream(string $prompt, callable $callback): void
    {
        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => true,
            'options' => $this->options,
        ];

        if (!empty($this->systemPrompt)) {
            $payload['system'] = $this->systemPrompt;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withOptions(['stream' => true])
                ->post($this->baseUrl . '/api/generate', $payload);

            if (!$response->successful()) {
                throw new \Exception('Ollama stream error: ' . $response->status());
            }

            $response->getBody()->rewind();
            $buffer = '';

            while (!$response->getBody()->eof()) {
                $chunk = $response->getBody()->read(1024);
                $buffer .= $chunk;

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    $line = trim($line);

                    if (empty($line)) {
                        continue;
                    }

                    try {
                        $data = json_decode($line, true);
                        if (isset($data['response'])) {
                            $callback($data['response']);
                        }
                        if (isset($data['done']) && $data['done']) {
                            $this->lastTokensUsed = $data['eval_count'] ?? null;
                            break 2;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to parse stream line', ['line' => $line]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Ollama stream failed', [
                'model' => $this->model,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * بررسی در دسترس بودن مدل
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get($this->baseUrl . '/api/tags');

            if (!$response->successful()) {
                return false;
            }

            $models = $response->json()['models'] ?? [];
            foreach ($models as $model) {
                if (isset($model['name']) && $model['name'] === $this->model) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::warning('Ollama availability check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * دریافت اطلاعات مدل
     */
    public function getModelInfo(): array
    {
        try {
            $response = Http::timeout(10)
                ->post($this->baseUrl . '/api/show', [
                    'model' => $this->model,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => 'Failed to get model info'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * لیست مدل‌های موجود
     */
    public function listModels(): array
    {
        try {
            $response = Http::timeout(5)
                ->get($this->baseUrl . '/api/tags');

            if ($response->successful()) {
                return $response->json()['models'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::warning('Failed to list Ollama models', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * محاسبه امتیاز اطمینان
     */
    private function calculateConfidence(string $response): float
    {
        $confidence = 0.7;

        // افزایش اطمینان برای پاسخ‌های بلندتر
        $length = strlen($response);
        if ($length > 500) {
            $confidence += 0.15;
        } elseif ($length > 200) {
            $confidence += 0.1;
        } elseif ($length < 50) {
            $confidence -= 0.2;
        }

        // افزایش اطمینان برای پاسخ‌های ساختاریافته
        if (preg_match('/\n[\d\-]+\./', $response)) {
            $confidence += 0.05;
        }

        return min(max($confidence, 0), 1);
    }
}
