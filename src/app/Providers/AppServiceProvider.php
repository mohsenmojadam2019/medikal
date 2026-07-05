<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Sms\SmsManager;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsManager::class, function ($app) {
            return new SmsManager();
        });
    }

    public function boot(): void
    {
        //
    }
}
