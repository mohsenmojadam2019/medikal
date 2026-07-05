<?php

namespace App\Services\Sms\Adapters;

use App\Services\Sms\Contracts\SmsInterface;
use Illuminate\Support\Facades\Log;

class FakeSmsAdapter implements SmsInterface
{
    // کدهای تستی ۴ رقمی برای شماره‌های خاص
    protected array $testNumbers = [
        '09034325329' => '1234',
        '09123456789' => '1111',
        '09222222222' => '2222',
        '09333333333' => '3333',
        '09999999999' => '9999',
    ];

    public function send(string $to, string $message): array
    {
        // استخراج کد از پیام (اگر پیام شامل کد باشد)
        $code = $this->extractCodeFromMessage($message);
        
        // اگر کد در پیام نبود، از لیست تستی استفاده کن
        if (!$code) {
            $code = $this->testNumbers[$to] ?? str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        Log::info('📱 [FAKE SMS]', [
            'to' => $to,
            'code' => $code,
            'message' => $message,
            'time' => now()->toDateTimeString(),
        ]);

        return [
            'success' => true,
            'message_id' => 'fake_' . time() . '_' . rand(1000, 9999),
            'gateway' => 'fake',
            'debug_code' => $code,
        ];
    }

    public function sendPattern(string $to, string $patternCode, array $params): array
    {
        // استخراج کد از پارامترها
        $code = $params['token'] ?? $params['code'] ?? null;
        
        // اگر کد در پارامترها نبود، از لیست تستی استفاده کن
        if (!$code) {
            $code = $this->testNumbers[$to] ?? str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        Log::info('📱 [FAKE SMS PATTERN]', [
            'to' => $to,
            'pattern' => $patternCode,
            'code' => $code,
            'params' => $params,
            'time' => now()->toDateTimeString(),
        ]);

        return [
            'success' => true,
            'message_id' => 'fake_pattern_' . time() . '_' . rand(1000, 9999),
            'gateway' => 'fake',
            'debug_code' => $code,
        ];
    }

    public function getBalance(): float
    {
        return 10000000;
    }

    public function getGatewayName(): string
    {
        return 'fake';
    }

    /**
     * استخراج کد ۴ رقمی از متن پیام
     */
    protected function extractCodeFromMessage(string $message): ?string
    {
        // الگوی کد ۴ رقمی
        if (preg_match('/\b(\d{4})\b/', $message, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
