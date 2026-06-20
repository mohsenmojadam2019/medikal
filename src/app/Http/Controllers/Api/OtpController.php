<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OtpCode;
use App\Services\Sms\SmsGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    protected SmsGatewayManager $sms;

    public function __construct(SmsGatewayManager $sms)
    {
        $this->sms = $sms;
    }

    /**
     * ارسال کد OTP
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|size:11',
        ]);

        $phone = $validated['phone'];

        // حذف کدهای قبلی منقضی شده این شماره
        OtpCode::where('phone', $phone)
            ->where('expires_at', '<', now())
            ->delete();

        // بررسی درخواست‌های اخیر (هر ۲ دقیقه یکبار)
        $recentRequest = OtpCode::where('phone', $phone)
            ->where('created_at', '>', now()->subMinutes(2))
            ->first();

        if ($recentRequest) {
            return response()->json([
                'success' => false,
                'message' => 'لطفاً ۲ دقیقه صبر کنید و دوباره تلاش کنید'
            ], 429);
        }

        // تولید کد ۵ رقمی
        $code = rand(10000, 99999);

        // پیدا کردن کاربر (اگر وجود داشته باشد)
        $user = User::where('phone', $phone)->first();

        // ذخیره کد در دیتابیس
        $otpCode = OtpCode::create([
            'user_id' => $user?->id,
            'phone' => $phone,
            'code' => (string) $code,
            'type' => 'login',
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
            'attempts' => 0,
            'ip_address' => $request->ip(),
        ]);

        // ارسال پیامک با اولویت‌بندی (اگر اولی خطا داد، دومی برود)
        $result = $this->sms->sendWithFallback($phone, "کد تأیید شما: {$code}");

        if (!$result['success']) {
            Log::error('SMS sending failed', [
                'phone' => $phone,
                'error' => $result['error'] ?? 'Unknown',
                'gateway' => $result['gateway'] ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیامک. لطفاً دوباره تلاش کنید.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'کد تأیید ارسال شد',
            'expires_in' => 300,
            'gateway' => $result['gateway'] ?? 'unknown',
            'debug_code' => $code, // فقط برای تست
        ]);
    }

    /**
     * تایید کد OTP
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|size:11',
            'code' => 'required|string|size:5',
            'name' => 'nullable|string|max:255',
        ]);

        $phone = $validated['phone'];
        $code = $validated['code'];
        $name = $validated['name'] ?? null;

        // بررسی کد در دیتابیس
        $otpCode = OtpCode::where('phone', $phone)
            ->where('code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpCode) {
            return response()->json([
                'success' => false,
                'message' => 'کد وارد شده صحیح نیست یا منقضی شده است'
            ], 400);
        }

        // افزایش تعداد تلاش‌ها
        $otpCode->increment('attempts');

        // علامت زدن کد به عنوان استفاده شده
        $otpCode->update(['is_used' => true]);

        // پیدا کردن یا ایجاد کاربر
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            // ثبت‌نام خودکار
            $user = User::create([
                'name' => $name ?? 'کاربر ' . substr($phone, -4),
                'phone' => $phone,
                'email' => $phone . '@temp.com',
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'phone_verified_at' => now(),
            ]);
        } else {
            // بروزرسانی زمان تأیید تلفن اگر قبلاً تأیید نشده
            if (!$user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }
        }

        // حذف توکن‌های قبلی
        $user->tokens()->delete();

        // ایجاد توکن جدید
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ورود با موفقیت انجام شد',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
            ],
            'token' => $token,
        ]);
    }
}
