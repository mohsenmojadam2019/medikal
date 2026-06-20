<?php

namespace App\Services\Sms\Adapters;

use App\Services\Sms\Contracts\SmsInterface;
use Illuminate\Support\Facades\Log;

class FakeSmsAdapter implements SmsInterface
{
    protected array $testNumbers = [
        '09034325329' => '12345',
        '09123456789' => '11111',
        '09222222222' => '22222',
        '09333333333' => '33333',
    ];

    public function send(string $to, string $message): array
    {
        $code = $this->testNumbers[$to] ?? rand(10000, 99999);

        Log::info('📱 [FAKE SMS]', [
            'to' => $to,
            'code' => $code,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'message_id' => 'fake_' . time(),
            'gateway' => 'fake',
            'debug_code' => $code,
        ];
    }

    public function sendPattern(string $to, string $patternCode, array $params): array
    {
        $code = $this->testNumbers[$to] ?? ($params['token'] ?? rand(10000, 99999));

        Log::info('📱 [FAKE SMS PATTERN]', [
            'to' => $to,
            'pattern' => $patternCode,
            'code' => $code,
        ]);

        return [
            'success' => true,
            'message_id' => 'fake_pattern_' . time(),
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
}
