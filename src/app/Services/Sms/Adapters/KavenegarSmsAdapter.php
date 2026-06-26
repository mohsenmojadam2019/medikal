<?php

namespace App\Services\Sms\Adapters;

use App\Services\Sms\Contracts\SmsInterface;
use Kavenegar\KavenegarApi;
use Kavenegar\Exceptions\ApiException;
use Illuminate\Support\Facades\Log;

class KavenegarSmsAdapter implements SmsInterface
{
    protected ?KavenegarApi $api = null;
    protected string $sender;
    protected string $apiKey;
    protected bool $isAvailable = false;

    public function __construct(array $config = [])
    {
        $this->apiKey = $config['api_key'] ?? config('sms.gateways.kavenegar.api_key', '');
        $this->sender = $config['sender'] ?? config('sms.gateways.kavenegar.sender', '10004346');

        if (!empty($this->apiKey)) {
            try {
                $this->api = new KavenegarApi($this->apiKey);
                $this->isAvailable = true;
            } catch (\Exception $e) {
                Log::warning('Kavenegar API initialization failed', ['error' => $e->getMessage()]);
            }
        }
    }

    public function send(string $to, string $message): array
    {
        if (!$this->api) {
            return [
                'success' => false,
                'error' => 'Kavenegar API not configured',
                'gateway' => 'kavenegar'
            ];
        }

        try {
            $result = $this->api->Send($this->sender, $to, $message);

            return [
                'success' => true,
                'message_id' => $result->messageid,
                'status' => $result->status,
                'cost' => $result->cost,
                'gateway' => 'kavenegar'
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->errorMessage(),
                'code' => $e->getCode(),
                'gateway' => 'kavenegar'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'kavenegar'
            ];
        }
    }

    public function sendPattern(string $to, string $patternCode, array $params): array
    {
        if (!$this->api) {
            return [
                'success' => false,
                'error' => 'Kavenegar API not configured',
                'gateway' => 'kavenegar'
            ];
        }

        try {
            $result = $this->api->VerifyLookup(
                $to,
                $params['token'] ?? '',
                $params['token2'] ?? '',
                $params['token3'] ?? '',
                $patternCode
            );

            return [
                'success' => true,
                'message_id' => $result->messageid,
                'status' => $result->status,
                'gateway' => 'kavenegar'
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->errorMessage(),
                'code' => $e->getCode(),
                'gateway' => 'kavenegar'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'kavenegar'
            ];
        }
    }

    public function getBalance(): float
    {
        if (!$this->api) {
            return 0;
        }

        try {
            $result = $this->api->AccountInfo();
            return (float)$result->remaincredit;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getGatewayName(): string
    {
        return 'kavenegar';
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable && $this->api !== null;
    }
}
