<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * تلاش برای ورود کاربر
     */
    public function login(array $credentials, bool $remember = false): ?User
    {
        if (Auth::attempt($credentials, $remember)) {
            return Auth::user();
        }
        return null;
    }

    /**
     * ثبت‌نام کاربر جدید
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // اختصاص نقش پیش‌فرض
        $user->assignRole('admin');

        return $user;
    }

    /**
     * خروج از سیستم
     */
    public function logout(): void
    {
        Auth::logout();
    }

    /**
     * بررسی آیا کاربر لاگین است
     */
    public function isAuthenticated(): bool
    {
        return Auth::check();
    }

    /**
     * دریافت کاربر جاری
     */
    public function getCurrentUser(): \Illuminate\Contracts\Auth\Authenticatable
    {
        return Auth::user();
    }

    /**
     * به‌روزرسانی زمان آخرین ورود
     */
    public function updateLastLogin(User $user, string $ip): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
