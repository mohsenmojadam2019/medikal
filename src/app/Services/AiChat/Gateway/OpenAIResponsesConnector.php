<?php

namespace App\Services\AiChat\Gateway;

use App\Contracts\AiChat\AIConnectorInterface;
use App\Models\AiChat\AIProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIResponsesConnector implements AIConnectorInterface
{
    public function generate(
        AIProvider $provider,
        array $messages,
        array $options = []
    ): array {
        $model = trim(
            (string) (
                $options['model']
                ?? $provider->default_model
                ?? 'gpt-5-mini'
            )
        );

        $payload = [
            'model' => $model,
            'input' => $this->buildInput($messages),
        ];

        $maxOutputTokens =
            $options['max_output_tokens']
            ?? $options['max_tokens']
            ?? null;

        if ($maxOutputTokens !== null) {
            $payload['max_output_tokens'] = max(
                16,
                (int) $maxOutputTokens
            );
        }

        $response = $this->request($provider)
            ->post(
                $this->baseUrl($provider).'/responses',
                $payload
            );

        $this->ensureSuccess($response, $payload);

        $data = $response->json();
        $text = $this->extractText($data);

        if ($text === '') {
            throw new RuntimeException(
                "OpenAI پاسخ متنی برنگرداند.\n\n".
                json_encode(
                    $data,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_PRETTY_PRINT
                )
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
                    data_get($data, 'usage.input_tokens'),

                'output_tokens' =>
                    data_get($data, 'usage.output_tokens'),

                'total_tokens' =>
                    data_get($data, 'usage.total_tokens'),
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
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ]
        );
    }

    public function models(
        AIProvider $provider
    ): array {
        $response = $this->request($provider)
            ->get(
                $this->baseUrl($provider).'/models'
            );

        $this->ensureSuccess($response);

        return collect(
            $response->json('data', [])
        )
            ->filter(
                fn ($model) =>
                    is_array($model) &&
                    filled($model['id'] ?? null)
            )
            ->map(
                fn ($model) => [
                    'id' => $model['id'],
                    'name' => $model['id'],
                    'owned_by' =>
                        $model['owned_by'] ?? null,
                ]
            )
            ->sortBy('id')
            ->values()
            ->all();
    }

    private function request(
        AIProvider $provider
    ): PendingRequest {
        $apiKey = trim(
            (string) $provider->api_key
        );

        if ($apiKey === '') {
            throw new RuntimeException(
                'کلید OpenAI ثبت نشده است.'
            );
        }

        $headers = [
            'Authorization' => 'Bearer '.$apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (filled($provider->organization)) {
            $headers['OpenAI-Organization'] =
                trim(
                    (string) $provider->organization
                );
        }

        if (filled($provider->project)) {
            $headers['OpenAI-Project'] =
                trim(
                    (string) $provider->project
                );
        }

        return Http::withHeaders($headers)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(20)
            ->timeout(120);
    }

    private function baseUrl(
        AIProvider $provider
    ): string {
        return rtrim(
            trim(
                (string) (
                    $provider->base_url
                    ?: 'https://api.openai.com/v1'
                )
            ),
            '/'
        );
    }

    private function buildInput(
        array $messages
    ): string {
        $text = collect($messages)
            ->filter(
                fn ($message) =>
                    is_array($message) &&
                    filled($message['content'] ?? null)
            )
            ->map(function (array $message) {
                $role = strtolower(
                    (string) (
                        $message['role'] ?? 'user'
                    )
                );

                $label = match ($role) {
                    'system',
                    'developer' => 'Instructions',

                    'assistant' => 'Assistant',

                    default => 'User',
                };

                return $label.":\n".
                    trim(
                        (string) $message['content']
                    );
            })
            ->implode("\n\n");

        return $text !== ''
            ? $text
            : 'فقط بنویس: اتصال با موفقیت برقرار شد.';
    }

    private function extractText(
        array $data
    ): string {
        if (filled($data['output_text'] ?? null)) {
            return trim(
                (string) $data['output_text']
            );
        }

        $parts = [];

        foreach ($data['output'] ?? [] as $output) {
            foreach (
                $output['content'] ?? []
                as $content
            ) {
                if (
                    ($content['type'] ?? null)
                    === 'output_text'
                    &&
                    filled($content['text'] ?? null)
                ) {
                    $parts[] = trim(
                        (string) $content['text']
                    );
                }
            }
        }

        return trim(
            implode("\n", $parts)
        );
    }

    private function ensureSuccess(
        Response $response,
        ?array $payload = null
    ): void {
        if ($response->successful()) {
            return;
        }

        $body = trim($response->body());
        $json = $response->json();

        $message =
            data_get($json, 'error.message')
            ?? data_get($json, 'message')
            ?? $body
            ?: 'خطای نامشخص';

        throw new RuntimeException(
            "خطای کامل OpenAI\n".
            "HTTP: {$response->status()}\n".
            "Request ID: ".
            ($response->header('x-request-id') ?: 'نامشخص').
            "\nType: ".
            (data_get($json, 'error.type') ?: 'نامشخص').
            "\nCode: ".
            (data_get($json, 'error.code') ?: 'نامشخص').
            "\nParameter: ".
            (data_get($json, 'error.param') ?: 'نامشخص').
            "\nMessage: {$message}\n\n".
            "Response:\n{$body}\n\n".
            "Payload:\n".
            (
                $payload
                    ? json_encode(
                        $payload,
                        JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES |
                        JSON_PRETTY_PRINT
                    )
                    : 'ندارد'
            )
        );
    }
}
