<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OtpCode extends Model
{
    protected $fillable = [
        'mobile',
        'code',
        'type',
        'attempts',
        'is_used',
        'expires_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * ایجاد کد OTP جدید برای شماره موبایل
     */
    public static function createForMobile(string $mobile): self
    {
        // حذف/بی‌استفاده کردن کدهای قبلی استفاده نشده
        self::where('mobile', $mobile)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // تولید کد ۴ رقمی تصادفی
        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return self::create([
            'mobile' => $mobile,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(5),
            'is_used' => false,
            'attempts' => 0,
        ]);
    }

    /**
     * بررسی و تایید کد OTP
     */
    public static function verify(string $mobile, string $code): ?self
    {
        $otp = self::where('mobile', $mobile)
            ->where('code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($otp) {
            $otp->update([
                'is_used' => true,
                'attempts' => $otp->attempts + 1,
            ]);
            return $otp;
        }

        // ثبت تلاش ناموفق
        $failedOtp = self::where('mobile', $mobile)
            ->where('code', $code)
            ->where('is_used', false)
            ->first();

        if ($failedOtp) {
            $failedOtp->increment('attempts');
        }

        return null;
    }

    /**
     * بررسی معتبر بودن کد
     */
    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }

    /**
     * بررسی منقضی شدن کد
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * علامت‌گذاری به عنوان استفاده شده
     */
    public function markAsUsed(): void
    {
        $this->update(['is_used' => true]);
    }

    /**
     * افزایش تعداد تلاش‌ها
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
