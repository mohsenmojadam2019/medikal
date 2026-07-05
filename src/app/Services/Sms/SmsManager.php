<?php

namespace App\Services\Sms;

use App\Services\Sms\Adapters\FakeSmsAdapter;

class SmsManager
{
    protected $adapter;

    public function __construct()
    {
        // فعلاً از Fake استفاده میکنیم
        $this->adapter = new FakeSmsAdapter();
    }

    public function send(string $to, string $message): array
    {
        return $this->adapter->send($to, $message);
    }

    public function sendPattern(string $to, string $patternCode, array $params): array
    {
        return $this->adapter->sendPattern($to, $patternCode, $params);
    }
}
