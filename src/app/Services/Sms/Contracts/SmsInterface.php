<?php

namespace App\Services\Sms\Contracts;

interface SmsInterface
{
    /**
     * ارسال پیام ساده
     */
    public function send(string $to, string $message): array;

    /**
     * ارسال پیام با الگو
     */
    public function sendPattern(string $to, string $patternCode, array $params): array;

    /**
     * دریافت اعتبار
     */
    public function getBalance(): float;

    /**
     * دریافت نام درگاه
     */
    public function getGatewayName(): string;
}
