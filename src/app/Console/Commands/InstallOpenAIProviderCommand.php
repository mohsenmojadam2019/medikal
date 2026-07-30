<?php

namespace App\Console\Commands;

use App\Models\AiChat\AIProvider;
use App\Services\AiChat\Gateway\AIProviderManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class InstallOpenAIProviderCommand extends Command
{
    protected $signature =
        'ai:openai
        {--model=gpt-5-mini}
        {--test}';

    protected $description =
        'Create or update the default OpenAI provider';

    public function handle(
        AIProviderManager $manager
    ): int {
        $apiKey = env('OPENAI_API_KEY');

        if (blank($apiKey)) {
            $this->error(
                'OPENAI_API_KEY در فایل .env ثبت نشده است.'
            );

            return self::FAILURE;
        }

        $provider = DB::transaction(
            function () use ($apiKey) {
                AIProvider::query()
                    ->update(['is_default' => false]);

                return AIProvider::updateOrCreate(
                    ['slug' => 'openai'],
                    [
                        'name' => 'OpenAI',
                        'driver' => 'openai',
                        'base_url' =>
                            'https://api.openai.com/v1',
                        'api_key' => $apiKey,
                        'default_model' =>
                            (string) $this->option('model'),
                        'options' => [
                            'timeout' => 90,
                            'connect_timeout' => 15,
                            'retries' => 2,
                            'max_output_tokens' => 700,
                        ],
                        'is_active' => true,
                        'is_default' => true,
                    ]
                );
            }
        );

        $this->info(
            'OpenAI ذخیره شد. مدل: '.
            $provider->default_model
        );

        if (!$this->option('test')) {
            return self::SUCCESS;
        }

        try {
            $result = $manager->test(
                $provider,
                'فقط بنویس: اتصال با موفقیت برقرار شد.'
            );

            $provider->update([
                'last_tested_at' => now(),
                'last_test_success' => true,
                'last_test_message' => 'تست موفق',
            ]);

            $this->newLine();
            $this->info('تست موفق:');
            $this->line($result['text']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $provider->update([
                'last_tested_at' => now(),
                'last_test_success' => false,
                'last_test_message' =>
                    $exception->getMessage(),
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
