<?php

namespace App\Services\Sms\Adapters;

use App\Services\Sms\Contracts\SmsInterface;

class MeliPayamakSmsAdapter implements SmsInterface
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $sender;

    public function __construct()
    {
        $this->baseUrl = config('sms.gateways.melipayamak.base_url', 'https://console.melipayamak.com');
        $this->apiKey = config('sms.gateways.melipayamak.api_key', '');
        $this->sender = config('sms.gateways.melipayamak.sender', '50004001231003');
    }

    public function send(string $to, string $message): array
    {
        $url = $this->baseUrl . '/api/send/simple/' . $this->apiKey;

        $data = [
            'from' => $this->sender,
            'to' => $to,
            'text' => $message
        ];

        $result = $this->curlRequest($url, $data);

        return $this->parseResult($result, 'melipayamak');
    }

    public function sendPattern(string $to, string $patternCode, array $params): array
    {
        $url = $this->baseUrl . '/api/send/shared/' . $this->apiKey;

        $data = [
            'bodyId' => (int) $patternCode,
            'to' => $to,
            'args' => $params
        ];

        $result = $this->curlRequest($url, $data);

        return $this->parseResult($result, 'melipayamak');
    }

    protected function curlRequest(string $url, array $data): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    protected function parseResult(string $result, string $gateway): array
    {
        $decoded = json_decode($result, true);

        if (isset($decoded['recId']) || isset($decoded['recId'])) {
            return [
                'success' => true,
                'message_id' => $decoded['recId'] ?? $decoded['id'] ?? null,
                'gateway' => $gateway
            ];
        }

        return [
            'success' => false,
            'error' => $decoded['message'] ?? 'Unknown error',
            'code' => $decoded['code'] ?? null,
            'gateway' => $gateway
        ];
    }

    public function getBalance(): float
    {
        $url = $this->baseUrl . '/api/credit/' . $this->apiKey;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($result, true);
        return (float) ($decoded['credit'] ?? 0);
    }

    public function getGatewayName(): string
    {
        return 'melipayamak';
    }
}
