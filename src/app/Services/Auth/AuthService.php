<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Services\Sms\SmsManager;

class AuthService
{
    protected SmsManager $smsManager;

    public function __construct(SmsManager $smsManager)
    {
        $this->smsManager = $smsManager;
    }

    public function loginWithMobile(string $mobile): array
    {
        Log::info('🔐 Login request for mobile: ' . $mobile);

        $user = User::where('mobile', $mobile)->first();

        if (is_null($user)) {
            Log::info('👤 Creating new user for mobile: ' . $mobile);
            $user = User::create([
                'mobile' => $mobile,
                'is_active' => true,
            ]);
        }

        if (!$user->is_active) {
            Log::warning('⚠️ Inactive user: ' . $mobile);
            throw new \Exception('حساب کاربری شما غیرفعال است');
        }

        // ایجاد OTP در دیتابیس
        $otp = OtpCode::createForMobile($mobile);

        Log::info('📱 OTP Code created for mobile: ' . $mobile . ' => ' . $otp->code);

        // ارسال SMS (با درگاه fake فعلاً)
        try {
            $this->smsManager->send(
                $mobile,
                "کد تایید شما: {$otp->code}\nدکتر وب"
            );
            Log::info('✅ SMS sent to: ' . $mobile);
        } catch (\Exception $e) {
            Log::error('❌ SMS failed: ' . $e->getMessage());
        }

        return [
            'user_id' => $user->id,
            'mobile' => $mobile,
            'message' => 'کد تایید به شماره موبایل شما ارسال شد',
            'expires_in' => 300,
        ];
    }

    public function verifyOtp(string $mobile, string $code): array
    {
        Log::info('🔍 Verifying OTP', [
            'mobile' => $mobile,
            'code' => $code,
        ]);

        $otp = OtpCode::verify($mobile, $code);

        if (!$otp) {
            Log::warning('❌ Invalid OTP', [
                'mobile' => $mobile,
                'code' => $code,
            ]);
            throw new \Exception('کد تایید نامعتبر یا منقضی شده است');
        }

        $user = User::where('mobile', $mobile)->first();

        if (!$user) {
            Log::error('❌ User not found', ['mobile' => $mobile]);
            throw new \Exception('کاربر یافت نشد');
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        Log::info('✅ User logged in', [
            'user_id' => $user->id,
            'mobile' => $mobile,
        ]);

        return [
            'user' => $user,
            'token' => $token,
            'message' => 'ورود با موفقیت انجام شد',
        ];
    }

    public function loginWithEmail(string $email, string $password): array
    {
        Log::info('🔐 Login with email: ' . $email);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            Log::warning('❌ Invalid email/password', ['email' => $email]);
            throw new \Exception('ایمیل یا رمز عبور اشتباه است');
        }

        if (!$user->is_active) {
            Log::warning('⚠️ Inactive user', ['email' => $email]);
            throw new \Exception('حساب کاربری شما غیرفعال است');
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        Log::info('✅ User logged in', [
            'user_id' => $user->id,
            'email' => $email,
        ]);

        return [
            'user' => $user,
            'token' => $token,
            'message' => 'ورود با موفقیت انجام شد',
        ];
    }

    public function logout($user): void
    {
        if ($user) {
            $user->currentAccessToken()->delete();
            Log::info('🚪 User logged out', ['user_id' => $user->id]);
        }
    }
}
