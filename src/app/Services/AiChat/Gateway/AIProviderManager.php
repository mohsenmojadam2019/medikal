<?php

namespace App\Services\AiChat\Gateway;

use App\Contracts\AiChat\AIConnectorInterface;
use App\Models\AiChat\AIProvider;
use InvalidArgumentException;
use RuntimeException;

class AIProviderManager
{
    public function connector(
        AIProvider $provider
    ): AIConnectorInterface {
        return match ($provider->driver) {
            'openai' =>
                app(OpenAIResponsesConnector::class),

            'openai_compatible' =>
                app(OpenAICompatibleConnector::class),

            'ollama' =>
                app(OllamaConnector::class),

            default =>
                throw new InvalidArgumentException(
                    "درایور {$provider->driver} پشتیبانی نمی‌شود."
                ),
        };
    }

    public function defaultProvider(): AIProvider
    {
        $provider = AIProvider::query()
            ->active()
            ->default()
            ->first()
            ?? AIProvider::query()
                ->active()
                ->first();

        if (!$provider) {
            throw new RuntimeException(
                'هیچ ارائه‌دهنده هوش مصنوعی فعالی ثبت نشده است.'
            );
        }

        return $provider;
    }

    public function generate(
        array $messages,
        ?AIProvider $provider = null,
        array $options = []
    ): array {
        $provider ??= $this->defaultProvider();

        if (!$provider->is_active) {
            throw new RuntimeException(
                'ارائه‌دهنده انتخاب‌شده غیرفعال است.'
            );
        }

        return $this->connector($provider)
            ->generate(
                $provider,
                $messages,
                $options
            );
    }

    public function test(
        AIProvider $provider,
        string $prompt
    ): array {
        return $this->connector($provider)
            ->test($provider, $prompt);
    }

    public function models(
        AIProvider $provider
    ): array {
        return $this->connector($provider)
            ->models($provider);
    }
}
