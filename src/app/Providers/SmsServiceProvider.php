<?php


namespace App\Providers;

use App\Services\Sms\SmsManager;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsManager::class, function ($app) {
            return new SmsManager();
        });

        $this->app->alias(SmsManager::class, 'sms');
    }

    public function boot(): void
    {
        //
    }
}
