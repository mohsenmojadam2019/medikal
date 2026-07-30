<?php

use App\Http\Controllers\Admin\AiProviderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('admin/ai/providers')
    ->group(function () {
        Route::get(
            '/',
            [AiProviderController::class, 'index']
        );

        Route::post(
            '/',
            [AiProviderController::class, 'store']
        );

        Route::post(
            '/chat-test',
            [AiProviderController::class, 'chatTest']
        );

        Route::put(
            '/{provider}',
            [AiProviderController::class, 'update']
        );

        Route::delete(
            '/{provider}',
            [AiProviderController::class, 'destroy']
        );

        Route::post(
            '/{provider}/default',
            [AiProviderController::class, 'setDefault']
        );

        Route::post(
            '/{provider}/test',
            [AiProviderController::class, 'test']
        );

        Route::get(
            '/{provider}/models',
            [AiProviderController::class, 'models']
        );
    });
