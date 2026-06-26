<?php

namespace App\Services\Sms\Adapters;

use App\Services\Sms\Contracts\SmsInterface;
use Illuminate\Support\Facades\Log;

class MockSmsAdapter implements SmsInterface
{
    public function send(string $to, string $message): array
    {
        // لاگ پیام در محیط تست
        Log::info('Mock SMS Sent', [
            'to' => $to,
            'message' => $message,
            'time' => now()
        ]);

        return [
            'success' => true,
            'message_id' => 'mock_' . time() . '_' . rand(1000, 9999),
            'gateway' => 'mock'
        ];
    }

    public function sendPattern(string $to, string $patternCode, array $params): array
    {
        Log::info('Mock Pattern SMS Sent', [
            'to' => $to,
            'pattern' => $patternCode,
            'params' => $params,
            'time' => now()
        ]);

        return [
            'success' => true,
            'message_id' => 'mock_pattern_' . time() . '_' . rand(1000, 9999),
            'gateway' => 'mock'
        ];
    }

    public function getBalance(): float
    {
        return 1000000; // اعتبار تستی
    }

    public function getGatewayName(): string
    {
        return 'mock';
    }
}
