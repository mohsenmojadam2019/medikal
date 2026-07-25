<?php
// app/Services/AiChat/Providers/AIProviderFactory.php

namespace App\Services\AiChat\Providers;

use App\Contracts\AiChat\AIProviderInterface;
use App\Services\AiChat\System\ConfigManager;

class AIProviderFactory
{
    private array $providers = [];
    private string $defaultProvider;

    public function __construct(private ConfigManager $configManager)
    {
        $this->defaultProvider = $this->configManager->get('provider.default', 'ollama');
        $this->registerProviders();
    }

    public function registerProviders(): void
    {
        $this->providers = [
            'ollama' => OllamaProvider::class,
            'openai' => OpenAIProvider::class,
            'gemini' => GeminiProvider::class,
        ];
    }

    public function make(?string $provider = null): AIProviderInterface
    {
        $providerName = $provider ?? $this->defaultProvider;

        if (!isset($this->providers[$providerName])) {
            throw new \Exception("Provider '{$providerName}' not supported");
        }

        $class = $this->providers[$providerName];
        return new $class($this->configManager);
    }

    public function getDefaultProvider(): string
    {
        return $this->defaultProvider;
    }

    public function setDefaultProvider(string $provider): void
    {
        $this->defaultProvider = $provider;
        $this->configManager->set('provider.default', $provider);
    }

    public function getAvailableProviders(): array
    {
        $available = [];
        foreach ($this->providers as $name => $class) {
            $provider = new $class($this->configManager);
            if ($provider->isAvailable()) {
                $available[] = $name;
            }
        }
        return $available;
    }

    public function getAllProviders(): array
    {
        return array_keys($this->providers);
    }

    public function registerProvider(string $name, string $class): void
    {
        if (!class_exists($class)) {
            throw new \Exception("Class '{$class}' not found");
        }

        if (!is_subclass_of($class, AIProviderInterface::class)) {
            throw new \Exception("Class '{$class}' must implement AIProviderInterface");
        }

        $this->providers[$name] = $class;
    }
}
