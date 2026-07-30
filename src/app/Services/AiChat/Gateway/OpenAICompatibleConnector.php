<?php

namespace App\Services\AiChat\Gateway;

use App\Contracts\AiChat\AIConnectorInterface;
use App\Models\AiChat\AIProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAICompatibleConnector implements AIConnectorInterface
{
    public function generate(
        AIProvider $provider,
        array $messages,
        array $options = []
    ): array {
        $providerOptions = $provider->options ?? [];
        $model = $options['model']
            ?? $provider->default_model;

        $payload = [
            'model' => $model,
            'messages' => $this->normalizeMessages($messages),
            'stream' => false,
        ];

        $temperature = $options['temperature']
            ?? $providerOptions['temperature']
            ?? 0.4;

        if ($temperature !== null) {
            $payload['temperature'] = (float) $temperature;
        }

        $maxTokens = $options['max_tokens']
            ?? $providerOptions['max_tokens']
            ?? 700;

        if ($maxTokens) {
            $payload['max_tokens'] = (int) $maxTokens;
        }

        $endpoint =
            $providerOptions['chat_path']
            ?? '/chat/completions';

        $response = $this->request($provider)
            ->post(
                $this->baseUrl($provider).$endpoint,
                $payload
            );

        $this->ensureSuccess($response);

        $data = $response->json();
        $text = trim(
            (string) (
                $data['choices'][0]['message']['content']
                ?? ''
            )
        );

        if ($text === '') {
            throw new RuntimeException(
                'ارائه‌دهنده پاسخ متنی برنگرداند.'
            );
        }

        return [
            'text' => $text,
            'provider' => $provider->slug,
            'driver' => $provider->driver,
            'model' => $data['model'] ?? $model,
            'response_id' => $data['id'] ?? null,
            'usage' => [
                'input_tokens' =>
                    $data['usage']['prompt_tokens'] ?? null,
                'output_tokens' =>
                    $data['usage']['completion_tokens'] ?? null,
                'total_tokens' =>
                    $data['usage']['total_tokens'] ?? null,
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
        $path =
            $provider->options['models_path']
            ?? '/models';

        $response = $this->request($provider)
            ->get($this->baseUrl($provider).$path);

        $this->ensureSuccess($response);

        $items = $response->json('data');

        if (!is_array($items)) {
            $items = $response->json('models', []);
        }

        return collect($items)
            ->map(function ($item) {
                $id = $item['id']
                    ?? $item['name']
                    ?? null;

                return $id
                    ? [
                        'id' => $id,
                        'name' => $id,
                        'owned_by' =>
                            $item['owned_by'] ?? null,
                    ]
                    : null;
            })
            ->filter()
            ->sortBy('id')
            ->values()
            ->all();
    }

    private function request(
        AIProvider $provider
    ): PendingRequest {
        if (blank($provider->api_key)) {
            throw new RuntimeException(
                'کلید API برای این ارائه‌دهنده ثبت نشده است.'
            );
        }

        $headers = [
            'Authorization' =>
                'Bearer '.$provider->api_key,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $customHeaders =
            $provider->options['headers'] ?? [];

        return Http::withHeaders(
            array_merge($headers, $customHeaders)
        )
            ->acceptJson()
            ->asJson()
            ->connectTimeout(
                (int) (
                    $provider->options['connect_timeout']
                    ?? 15
                )
            )
            ->timeout(
                (int) (
                    $provider->options['timeout']
                    ?? 90
                )
            )
            ->retry(
                (int) (
                    $provider->options['retries']
                    ?? 2
                ),
                800,
                throw: false
            );
    }

    private function baseUrl(
        AIProvider $provider
    ): string {
        if (blank($provider->base_url)) {
            throw new RuntimeException(
                'آدرس Base URL ثبت نشده است.'
            );
        }

        return rtrim($provider->base_url, '/');
    }

    private function normalizeMessages(
        array $messages
    ): array {
        return collect($messages)
            ->filter(
                fn ($message) =>
                    filled($message['content'] ?? null)
            )
            ->map(fn ($message) => [
                'role' =>
                    $message['role'] ?? 'user',
                'content' =>
                    (string) $message['content'],
            ])
            ->values()
            ->all();
    }

    private function ensureSuccess(
        Response $response
    ): void {
        if ($response->successful()) {
            return;
        }

        $message =
            $response->json('error.message')
            ?? $response->json('message')
            ?? $response->body();

        throw new RuntimeException(
            'خطای ارائه‌دهنده ('.
            $response->status().
            '): '.
            mb_substr((string) $message, 0, 1000)
        );
    }
}
