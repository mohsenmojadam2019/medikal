<?php

namespace App\Services\AiChat\Gateway;

use App\Contracts\AiChat\AIConnectorInterface;
use App\Models\AiChat\AIProvider;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaConnector implements AIConnectorInterface
{
    public function generate(
        AIProvider $provider,
        array $messages,
        array $options = []
    ): array {
        $providerOptions = $provider->options ?? [];
        $model = $options['model']
            ?? $provider->default_model;

        $response = Http::acceptJson()
            ->asJson()
            ->connectTimeout(
                (int) (
                    $providerOptions['connect_timeout']
                    ?? 10
                )
            )
            ->timeout(
                (int) (
                    $providerOptions['timeout']
                    ?? 120
                )
            )
            ->post(
                $this->baseUrl($provider).'/api/chat',
                [
                    'model' => $model,
                    'messages' => $messages,
                    'stream' => false,
                    'options' => [
                        'temperature' =>
                            $options['temperature']
                            ?? $providerOptions['temperature']
                            ?? 0.4,
                        'num_predict' =>
                            $options['max_tokens']
                            ?? $providerOptions['max_tokens']
                            ?? 700,
                    ],
                ]
            );

        $this->ensureSuccess($response);

        $data = $response->json();
        $text = trim(
            (string) (
                $data['message']['content']
                ?? $data['response']
                ?? ''
            )
        );

        if ($text === '') {
            throw new RuntimeException(
                'Ollama پاسخ متنی برنگرداند.'
            );
        }

        return [
            'text' => $text,
            'provider' => $provider->slug,
            'driver' => $provider->driver,
            'model' => $data['model'] ?? $model,
            'response_id' => null,
            'usage' => [
                'input_tokens' =>
                    $data['prompt_eval_count'] ?? null,
                'output_tokens' =>
                    $data['eval_count'] ?? null,
                'total_tokens' =>
                    isset($data['prompt_eval_count'])
                    || isset($data['eval_count'])
                        ? (
                            (int) (
                                $data['prompt_eval_count']
                                ?? 0
                            )
                            + (int) (
                                $data['eval_count']
                                ?? 0
                            )
                        )
                        : null,
            ],
            'raw' => $data,
        ];
    }

    public function test(
        AIProvider $provider,
        string $prompt
    ): array {
        return $this->generate(
            $provider,
            [
                [
                    'role' => 'system',
                    'content' =>
                        'فقط یک پاسخ کوتاه برای تست اتصال بده.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            [
                'max_tokens' => 120,
                'temperature' => 0.2,
            ]
        );
    }

    public function models(
        AIProvider $provider
    ): array {
        $response = Http::acceptJson()
            ->timeout(15)
            ->get($this->baseUrl($provider).'/api/tags');

        $this->ensureSuccess($response);

        return collect($response->json('models', []))
            ->map(fn ($item) => [
                'id' => $item['name'] ?? null,
                'name' => $item['name'] ?? null,
                'size' => $item['size'] ?? null,
                'modified_at' =>
                    $item['modified_at'] ?? null,
            ])
            ->filter(fn ($item) => filled($item['id']))
            ->values()
            ->all();
    }

    private function baseUrl(
        AIProvider $provider
    ): string {
        return rtrim(
            $provider->base_url
                ?: 'http://medikall-ollama:11434',
            '/'
        );
    }

    private function ensureSuccess(
        Response $response
    ): void {
        if ($response->successful()) {
            return;
        }

        $message =
            $response->json('error')
            ?? $response->json('message')
            ?? $response->body();

        throw new RuntimeException(
            'خطای Ollama ('.
            $response->status().
            '): '.
            mb_substr((string) $message, 0, 1000)
        );
    }
}
