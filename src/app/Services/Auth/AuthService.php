<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    protected SmsManager $smsManager;

    public function __construct(SmsManager $smsManager)
    {
        $this->smsManager = $smsManager;
    }

    public function loginWithMobile(string $mobile): array
    {
        $user = User::where('mobile', $mobile)->first();

        if (is_null($user)) {
            $user = User::create([
                'mobile' => $mobile,
                'is_active' => true,
            ]);
        }

        if ($user->is_active == false) {
            throw new \Exception('حساب کاربری شما غیرفعال است');
        }

        $otp = $this->generateOtp();

        cache()->put("otp_{$mobile}", [
            'code' => $otp,
            'user_id' => $user->id,
            'attempts' => 0,
        ], 300);

        $this->sendOtpSms($mobile, $otp);

        return [
            'user_id' => $user->id,
            'mobile' => $mobile,
            'message' => 'کد تایید به شماره موبایل شما ارسال شد',
            'expires_in' => 300,
        ];
    }

    public function verifyOtp(string $mobile, string $code): array
    {
        $cached = cache()->get("otp_{$mobile}");

        if (is_null($cached)) {
            throw new \Exception('کد تایید منقضی شده است');
        }

        if ($cached['attempts'] >= 5) {
            cache()->forget("otp_{$mobile}");
            throw new \Exception('تعداد تلاشها بیش از حد مجاز است');
        }

        if ($cached['code'] !== $code) {
            $cached['attempts']++;
            cache()->put("otp_{$mobile}", $cached, 300);
            throw new \Exception('کد تایید اشتباه است');
        }

        $user = User::find($cached['user_id']);
        if (is_null($user)) {
            throw new \Exception('کاربر یافت نشد');
        }

        $user->update([
            'mobile_verified_at' => now(),
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        cache()->forget("otp_{$mobile}");

        $token = $user->createToken('auth-token')->plainTextToken;

        if ($user->current_tenant_id) {
            session(['tenant_id' => $user->current_tenant_id]);
        }

        return [
            'user' => $user,
            'token' => $token,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'message' => 'ورود با موفقیت انجام شد',
        ];
    }

    public function loginWithEmail(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (is_null($user) || Hash::check($password, $user->password) == false) {
            throw new \Exception('ایمیل یا رمز عبور اشتباه است');
        }

        if ($user->is_active == false) {
            throw new \Exception('حساب کاربری شما غیرفعال است');
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        if ($user->current_tenant_id) {
            session(['tenant_id' => $user->current_tenant_id]);
        }

        return [
            'user' => $user,
            'token' => $token,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'message' => 'ورود با موفقیت انجام شد',
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
        session()->forget('tenant_id');
    }

    protected function generateOtp(): string
    {
        if (app()->environment('production')) {
            return str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        }
        return '1234';
    }

    protected function sendOtpSms(string $mobile, string $code): void
    {
        try {
            $pattern = config('sms.patterns.otp_login', 'otp-login');
            $this->smsManager->sendPattern($mobile, $pattern, ['token' => $code]);
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
