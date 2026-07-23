<?php
// app/Services/AiChat/Providers/GeminiProvider.php

namespace App\Services\AiChat\Providers;

use App\Contracts\AiChat\AIProviderInterface;
use App\Services\AiChat\System\ConfigManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AIProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $systemPrompt = '';
    private array $options = [];
    private ?int $lastTokensUsed = null;
    private ?float $lastConfidence = null;
    private float $timeout = 60;
    private int $maxRetries = 3;

    public function __construct(private ConfigManager $configManager)
    {
        $this->apiKey = $this->configManager->get('gemini.api_key', env('GEMINI_API_KEY'));
        $this->model = $this->configManager->get('gemini.model', 'gemini-1.5-pro');
        $this->timeout = $this->configManager->get('gemini.timeout', 60);
        $this->maxRetries = $this->configManager->get('gemini.max_retries', 3);
        $this->options = $this->configManager->get('gemini.options', [
            'temperature' => 0.7,
            'top_p' => 0.9,
            'max_tokens' => 500,
        ]);
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function setSystemPrompt(string $prompt): self
    {
        $this->systemPrompt = $prompt;
        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function generate(string $prompt): string
    {
        $this->lastTokensUsed = null;
        $this->lastConfidence = null;

        $contents = [];
        if (!empty($this->systemPrompt)) {
            $contents[] = ['role' => 'system', 'parts' => [['text' => $this->systemPrompt]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $prompt]]];

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $this->options['temperature'] ?? 0.7,
                'topP' => $this->options['top_p'] ?? 0.9,
                'maxOutputTokens' => $this->options['max_tokens'] ?? 500,
            ],
        ];

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout($this->timeout)
                    ->post("https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}", $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $this->lastTokensUsed = $data['usageMetadata']['totalTokenCount'] ?? null;
                    $this->lastConfidence = 0.85;
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                }

                throw new \Exception('Gemini API error: ' . $response->status() . ' - ' . $response->body());
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                Log::warning("Gemini attempt {$attempt} failed", [
                    'model' => $this->model,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $this->maxRetries) {
                    sleep(1);
                }
            }
        }

        Log::error('Gemini failed after retries', [
            'model' => $this->model,
            'error' => $lastException?->getMessage(),
        ]);

        throw new \Exception("Model '{$this->model}' unavailable after {$this->maxRetries} attempts");
    }

    public function chat(array $messages): string
    {
        // مشابه generate با ساختار Gemini
        $contents = [];
        if (!empty($this->systemPrompt)) {
            $contents[] = ['role' => 'system', 'parts' => [['text' => $this->systemPrompt]]];
        }
        foreach ($messages as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $this->options['temperature'] ?? 0.7,
                'topP' => $this->options['top_p'] ?? 0.9,
                'maxOutputTokens' => $this->options['max_tokens'] ?? 500,
            ],
        ];

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout($this->timeout)
                    ->post("https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}", $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $this->lastTokensUsed = $data['usageMetadata']['totalTokenCount'] ?? null;
                    $this->lastConfidence = 0.85;
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                }

                throw new \Exception('Gemini API error: ' . $response->status() . ' - ' . $response->body());
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                Log::warning("Gemini chat attempt {$attempt} failed", [
                    'model' => $this->model,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $this->maxRetries) {
                    sleep(1);
                }
            }
        }

        Log::error('Gemini chat failed after retries', [
            'model' => $this->model,
            'error' => $lastException?->getMessage(),
        ]);

        throw new \Exception("Model '{$this->model}' unavailable for chat");
    }

    public function stream(string $prompt, callable $callback): void
    {
        // Gemini stream implementation
        $contents = [];
        if (!empty($this->systemPrompt)) {
            $contents[] = ['role' => 'system', 'parts' => [['text' => $this->systemPrompt]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $prompt]]];

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $this->options['temperature'] ?? 0.7,
                'topP' => $this->options['top_p'] ?? 0.9,
                'maxOutputTokens' => $this->options['max_tokens'] ?? 500,
            ],
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withOptions(['stream' => true])
                ->post("https://generativelanguage.googleapis.com/v1/models/{$this->model}:streamGenerateContent?key={$this->apiKey}", $payload);

            if (!$response->successful()) {
                throw new \Exception('Gemini stream error: ' . $response->status());
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
                        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                            $callback($data['candidates'][0]['content']['parts'][0]['text']);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to parse Gemini stream line', ['line' => $line]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Gemini stream failed', [
                'model' => $this->model,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function listModels(): array
    {
        return [
            ['name' => 'gemini-1.5-pro', 'label' => 'Gemini 1.5 Pro'],
            ['name' => 'gemini-1.5-flash', 'label' => 'Gemini 1.5 Flash'],
            ['name' => 'gemini-1.0-pro', 'label' => 'Gemini 1.0 Pro'],
        ];
    }

    public function getModelInfo(): array
    {
        return [
            'provider' => 'gemini',
            'model' => $this->model,
            'available' => $this->isAvailable(),
        ];
    }

    public function getProviderName(): string
    {
        return 'gemini';
    }
}
