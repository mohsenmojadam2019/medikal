<?php

namespace App\AiChat\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AiChat\Chat\ChatService;
use App\Services\AiChat\Medical\MedicalFilterService;
use App\Services\AiChat\AI\OllamaClient;
use App\Services\AiChat\AI\PromptManager;
use App\Services\AiChat\System\ConfigManager;
use App\Services\AiChat\System\DataCleanupService;
use App\Services\AiChat\System\MetricsCollector;
use App\Services\AiChat\Chat\FileUploadService;

class AiChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ثبت سرویس‌ها به صورت Singleton
        $this->app->singleton(ChatService::class, function ($app) {
            return new ChatService(
                $app->make(MedicalFilterService::class),
                $app->make(OllamaClient::class),
                $app->make(PromptManager::class),
                $app->make(ConfigManager::class),
                $app->make(MetricsCollector::class)
            );
        });

        $this->app->singleton(MedicalFilterService::class, function ($app) {
            return new MedicalFilterService($app->make(ConfigManager::class));
        });

        $this->app->singleton(OllamaClient::class, function ($app) {
            return new OllamaClient($app->make(ConfigManager::class));
        });

        $this->app->singleton(PromptManager::class, function ($app) {
            return new PromptManager($app->make(ConfigManager::class));
        });

        $this->app->singleton(ConfigManager::class, function () {
            return new ConfigManager();
        });

        $this->app->singleton(DataCleanupService::class, function ($app) {
            return new DataCleanupService($app->make(ConfigManager::class));
        });

        $this->app->singleton(MetricsCollector::class, function () {
            return new MetricsCollector();
        });

        $this->app->singleton(FileUploadService::class, function ($app) {
            return new FileUploadService($app->make(ConfigManager::class));
        });

        // بارگذاری تنظیمات
        $this->mergeConfigFrom(
            __DIR__.'/../Config/chat.php', 'aichat'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../Config/prompts.php', 'aichat_prompts'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../Config/models.php', 'aichat_models'
        );
    }

    public function boot(): void
    {
        // بارگذاری مسیرها
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');

        // بارگذاری مهاجرت‌ها
        $this->loadMigrationsFrom(__DIR__.'/../../Database/Migrations');

        // انتشار تنظیمات
        $this->publishes([
            __DIR__.'/../Config/chat.php' => config_path('aichat.php'),
            __DIR__.'/../Config/prompts.php' => config_path('aichat_prompts.php'),
            __DIR__.'/../Config/models.php' => config_path('aichat_models.php'),
        ], 'aichat-config');

        $this->publishes([
            __DIR__.'/../../Database/Migrations' => database_path('migrations'),
        ], 'aichat-migrations');
    }
}
