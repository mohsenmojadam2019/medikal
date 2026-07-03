<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    use ApiResponse;

    /**
     * ورود ادمین با ایمیل و رمز عبور
     */
    public function loginWithEmail(string $email, string $password): array
    {
        // ۱. پیدا کردن کاربر
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['ایمیل یا رمز عبور اشتباه است'],
            ]);
        }

        // ۲. بررسی رمز عبور
        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['ایمیل یا رمز عبور اشتباه است'],
            ]);
        }

        // ۳. بررسی فعال بودن کاربر
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['حساب کاربری شما غیرفعال است'],
            ]);
        }

        // ۴. بررسی نقش ادمین
        if (!$user->hasRole(['admin', 'super_admin'])) {
            throw ValidationException::withMessages([
                'email' => ['شما دسترسی به پنل مدیریت ندارید'],
            ]);
        }

        // ۵. ایجاد توکن
        $token = $user->createToken('admin-token', ['*'])->plainTextToken;

        // ۶. به‌روزرسانی زمان آخرین ورود
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        return [
            'user' => $user->load('roles.permissions'),
            'token' => $token,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }

    /**
     * خروج ادمین
     */
    public function logout($user): void
    {
        if ($user) {
            $user->currentAccessToken()?->delete();
        }
    }

    /**
     * دریافت اطلاعات کاربر جاری
     */
    public function getCurrentUser($user): array
    {
        return [
            'user' => $user->load('primaryAddress', 'primaryAddress.province', 'primaryAddress.city'),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }

    /**
     * بررسی دسترسی ادمین
     */
    public function checkAdminAccess($user): bool
    {
        return $user && $user->hasRole(['admin', 'super_admin']);
    }
}
