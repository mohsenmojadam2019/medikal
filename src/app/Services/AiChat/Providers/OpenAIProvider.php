<?php
// app/Services/AiChat/Providers/OpenAIProvider.php

namespace App\Services\AiChat\Providers;

use App\Contracts\AiChat\AIProviderInterface;
use App\Services\AiChat\System\ConfigManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements AIProviderInterface
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
        $this->apiKey = $this->configManager->get('openai.api_key', env('OPENAI_API_KEY'));
        $this->model = $this->configManager->get('openai.model', 'gpt-4o-mini');
        $this->timeout = $this->configManager->get('openai.timeout', 60);
        $this->maxRetries = $this->configManager->get('openai.max_retries', 3);
        $this->options = $this->configManager->get('openai.options', [
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

        $messages = [];
        if (!empty($this->systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $this->systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->options['temperature'] ?? 0.7,
            'max_tokens' => $this->options['max_tokens'] ?? 500,
            'top_p' => $this->options['top_p'] ?? 0.9,
        ];

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.openai.com/v1/chat/completions', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $this->lastTokensUsed = $data['usage']['total_tokens'] ?? null;
                    $this->lastConfidence = 0.85;
                    return $data['choices'][0]['message']['content'] ?? '';
                }

                throw new \Exception('OpenAI API error: ' . $response->status() . ' - ' . $response->body());
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                Log::warning("OpenAI attempt {$attempt} failed", [
                    'model' => $this->model,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $this->maxRetries) {
                    sleep(1);
                }
            }
        }

        Log::error('OpenAI failed after retries', [
            'model' => $this->model,
            'error' => $lastException?->getMessage(),
        ]);

        throw new \Exception("Model '{$this->model}' unavailable after {$this->maxRetries} attempts");
    }

    public function chat(array $messages): string
    {
        $this->lastTokensUsed = null;
        $this->lastConfidence = null;

        if (!empty($this->systemPrompt)) {
            array_unshift($messages, ['role' => 'system', 'content' => $this->systemPrompt]);
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->options['temperature'] ?? 0.7,
            'max_tokens' => $this->options['max_tokens'] ?? 500,
            'top_p' => $this->options['top_p'] ?? 0.9,
        ];

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.openai.com/v1/chat/completions', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $this->lastTokensUsed = $data['usage']['total_tokens'] ?? null;
                    $this->lastConfidence = 0.85;
                    return $data['choices'][0]['message']['content'] ?? '';
                }

                throw new \Exception('OpenAI API error: ' . $response->status() . ' - ' . $response->body());
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                Log::warning("OpenAI chat attempt {$attempt} failed", [
                    'model' => $this->model,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $this->maxRetries) {
                    sleep(1);
                }
            }
        }

        Log::error('OpenAI chat failed after retries', [
            'model' => $this->model,
            'error' => $lastException?->getMessage(),
        ]);

        throw new \Exception("Model '{$this->model}' unavailable for chat");
    }

    public function stream(string $prompt, callable $callback): void
    {
        $messages = [];
        if (!empty($this->systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $this->systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->options['temperature'] ?? 0.7,
            'max_tokens' => $this->options['max_tokens'] ?? 500,
            'top_p' => $this->options['top_p'] ?? 0.9,
            'stream' => true,
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->withOptions(['stream' => true])
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$response->successful()) {
                throw new \Exception('OpenAI stream error: ' . $response->status());
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

                    if (empty($line) || !str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $json = substr($line, 6);
                    if ($json === '[DONE]') {
                        break 2;
                    }

                    try {
                        $data = json_decode($json, true);
                        if (isset($data['choices'][0]['delta']['content'])) {
                            $callback($data['choices'][0]['delta']['content']);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to parse OpenAI stream line', ['line' => $line]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('OpenAI stream failed', [
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
            ['name' => 'gpt-4o', 'label' => 'GPT-4o'],
            ['name' => 'gpt-4o-mini', 'label' => 'GPT-4o Mini'],
            ['name' => 'gpt-4-turbo', 'label' => 'GPT-4 Turbo'],
            ['name' => 'gpt-3.5-turbo', 'label' => 'GPT-3.5 Turbo'],
        ];
    }

    public function getModelInfo(): array
    {
        return [
            'provider' => 'openai',
            'model' => $this->model,
            'available' => $this->isAvailable(),
        ];
    }

    public function getProviderName(): string
    {
        return 'openai';
    }
}
