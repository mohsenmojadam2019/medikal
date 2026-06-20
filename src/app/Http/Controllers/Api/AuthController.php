<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PhoneLoginRequest;
use App\Http\Requests\Api\VerifyCodeRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\Sms\SmsManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $smsManager;

    public function __construct(SmsManager $smsManager)
    {
        $this->smsManager = $smsManager;
    }

    /**
     * ارسال کد تایید به شماره موبایل
     */
    public function sendCode(PhoneLoginRequest $request)
    {
        $phone = $request->phone;

        // پیدا کردن یا ایجاد کاربر
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            // ثبت‌نام خودکار
            $user = User::registerWithPhone($phone);
        }

        // تولید کد ۴ رقمی
        $code = VerificationCode::generate();
        $expiresAt = Carbon::now()->addMinutes(5);

        // ذخیره کد در دیتابیس
        VerificationCode::create([
            'user_id' => $user->id,
            'phone' => $phone,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        // ارسال پیامک
        try {
            $this->smsManager->send(
                $phone,
                "کد تایید شما: {$code}\nاین کد تا ۵ دقیقه اعتبار دارد."
            );
        } catch (\Exception $e) {
            Log::error('SMS send failed: ' . $e->getMessage());
            // در محیط توسعه، کد رو در لاگ نشون بدیم
            if (config('app.debug')) {
                Log::info("Verification code for {$phone}: {$code}");
            }
        }

        // برای تست، کد رو برگردونیم (در محیط توسعه)
        $responseData = ['message' => 'کد تایید ارسال شد.'];
        if (config('app.debug')) {
            $responseData['debug_code'] = $code;
        }

        return response()->json($responseData);
    }

    /**
     * تایید کد و ورود
     */
    public function verifyCode(VerifyCodeRequest $request)
    {
        $phone = $request->phone;
        $code = $request->code;

        // پیدا کردن کاربر
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return response()->json([
                'message' => 'کاربری با این شماره یافت نشد.'
            ], 404);
        }

        // پیدا کردن کد معتبر
        $verificationCode = VerificationCode::where('phone', $phone)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$verificationCode) {
            return response()->json([
                'message' => 'کد نامعتبر یا منقضی شده است.'
            ], 422);
        }

        // علامت‌گذاری کد به عنوان استفاده شده
        $verificationCode->update(['used' => true]);

        // تایید شماره موبایل
        if (!$user->isPhoneVerified()) {
            $user->update(['phone_verified_at' => Carbon::now()]);
        }

        // به‌روزرسانی آخرین ورود
        $user->update([
            'last_login_at' => Carbon::now(),
            'last_login_ip' => $request->ip(),
        ]);

        // ایجاد توکن Sanctum
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'ورود موفقیت‌آمیز بود.',
            'user' => $user->only(['id', 'name', 'phone', 'email']),
            'token' => $token,
        ]);
    }

    /**
     * ورود با ایمیل و رمز عبور (برای کاربران عادی)
     */
    public function emailLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'ایمیل یا رمز عبور اشتباه است.'
            ], 401);
        }

        // به‌روزرسانی آخرین ورود
        $user->update([
            'last_login_at' => Carbon::now(),
            'last_login_ip' => $request->ip(),
        ]);

        // حذف توکن‌های قبلی
        $user->tokens()->delete();

        // ایجاد توکن جدید
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'ورود موفقیت‌آمیز بود.',
            'user' => $user->only(['id', 'name', 'phone', 'email']),
            'token' => $token,
        ]);
    }

    /**
     * ثبت‌نام با ایمیل (اختیاری)
     */
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'name' => 'nullable|string|max:255',
        ]);

        $user = User::registerWithEmail($request->email, $request->password);

        if ($request->name) {
            $user->update(['name' => $request->name]);
        }

        return response()->json([
            'message' => 'ثبت‌نام موفقیت‌آمیز بود.',
            'user' => $user->only(['id', 'name', 'email']),
        ], 201);
    }

    /**
     * دریافت اطلاعات کاربر جاری
     */
    public function user(Request $request)
    {
        return response()->json($request->user()->load('roles'));
    }

    /**
     * بروزرسانی پروفایل
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|unique:users,phone,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only(['name', 'phone', 'email']));

        return response()->json([
            'message' => 'پروفایل با موفقیت بروزرسانی شد.',
            'user' => $user->fresh()->only(['id', 'name', 'phone', 'email']),
        ]);
    }

    /**
     * خروج از سیستم
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'خروج موفقیت‌آمیز بود.'
        ]);
    }
}
