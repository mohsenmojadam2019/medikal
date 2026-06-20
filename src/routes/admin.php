<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

Route::name('admin.')->group(function () {

    // ============================================
    // روت‌های احراز هویت (بدون نیاز به لاگین)
    // ============================================
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);
    });

    // ============================================
    // روت خروج
    // ============================================
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ============================================
    // روت‌های محافظت‌شده (همه کاربران لاگین شده)
    // ============================================
    Route::middleware(['auth'])->group(function () {

        // 📊 داشبورد (همه کاربران)
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // 👤 پروفایل (همه کاربران)
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
        Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.upload-avatar');

        // ============================================
        // روت‌های اختصاصی ادمین‌ها (با نقش)
        // ============================================
        Route::middleware(['admin'])->group(function () {

            // 👤 مدیریت کاربران
            Route::resource('users', UserController::class)->names('users');
            Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

            // 🛍️ مدیریت محصولات
//            Route::resource('products', ProductController::class)->names('products');
//            Route::post('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');

            // 📦 مدیریت سفارشات
//            Route::resource('orders', OrderController::class)->names('orders');
//            Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');

            // 🎯 مدیریت نقش‌ها و مجوزها
            Route::resource('roles', RoleController::class)->names('roles');
            Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
            Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.sync-permissions');
            Route::resource('permissions', PermissionController::class)->names('permissions');

            // ⚙️ تنظیمات
            Route::get('/settings', [SettingController::class, 'index'])->name('settings');
            Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
            Route::post('/settings/clear-cache', [SettingController::class, 'clearCache'])->name('settings.clear-cache');
        });

        // ============================================
        // روت‌های اختصاصی کاربران عادی (بدون نیاز به نقش خاص)
        // ============================================
//        Route::get('/my-orders', [OrderController::class, 'myOrders'])->name('my-orders');
//        Route::get('/my-orders/{order}', [OrderController::class, 'showMyOrder'])->name('my-orders.show');
    });
});
