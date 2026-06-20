<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ============================================
    // روت‌های عمومی
    // ============================================
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/categories', [ProductController::class, 'categories']);

    // ============================================
    // روت‌های احراز هویت
    // ============================================
    Route::prefix('auth')->name('auth.')->group(function () {

        // ورود با موبایل و کد تایید
        Route::post('/phone/send-code', [AuthController::class, 'sendCode'])->name('phone.send-code');
        Route::post('/phone/verify', [AuthController::class, 'verifyCode'])->name('phone.verify');

        // ورود با ایمیل و رمز عبور
        Route::post('/email/login', [AuthController::class, 'emailLogin'])->name('email.login');

        // ثبت‌نام
        Route::post('/register', [AuthController::class, 'register'])->name('register');

        // فراموشی رمز عبور
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
    });

    // ============================================
    // روت‌های محافظت‌شده با Sanctum
    // ============================================
    Route::middleware('auth:sanctum')->group(function () {

        // 👤 اطلاعات کاربر
        Route::get('/user', [AuthController::class, 'user'])->name('user');
        Route::put('/user/profile', [AuthController::class, 'updateProfile'])->name('user.update-profile');
        Route::post('/user/avatar', [AuthController::class, 'updateAvatar'])->name('user.update-avatar');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // 🛍️ سفارشات
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        // 📝 نظرات
        Route::post('/products/{product}/comments', [ProductController::class, 'addComment'])->name('products.add-comment');
        Route::get('/products/{product}/comments', [ProductController::class, 'getComments'])->name('products.get-comments');
    });
});
