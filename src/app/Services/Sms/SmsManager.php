<?php

namespace App\Services\Sms;

use App\Services\Sms\Contracts\SmsInterface;
use App\Services\Sms\Adapters\KavenegarSmsAdapter;
use App\Services\Sms\Adapters\MeliPayamakSmsAdapter;
use App\Services\Sms\Adapters\FakeSmsAdapter;
use InvalidArgumentException;

class SmsManager
{
    protected array $gateways = [];
    protected string $defaultGateway;
    protected ?SmsInterface $currentGateway = null;

    public function __construct()
    {
        $this->defaultGateway = config('sms.default', 'fake');
        $this->registerGateways();
    }

    protected function registerGateways(): void
    {
        $this->gateways = [
            'kavenegar' => KavenegarSmsAdapter::class,
            'melipayamak' => MeliPayamakSmsAdapter::class,
            'fake' => FakeSmsAdapter::class,
        ];
    }

    public function gateway(?string $name = null): SmsInterface
    {
        $gatewayName = $name ?? $this->defaultGateway;

        if (!isset($this->gateways[$gatewayName])) {
            throw new InvalidArgumentException("SMS gateway '{$gatewayName}' not supported");
        }

        if ($this->currentGateway && $this->currentGateway->getGatewayName() === $gatewayName) {
            return $this->currentGateway;
        }

        $gatewayClass = $this->gateways[$gatewayName];
        $this->currentGateway = new $gatewayClass();

        return $this->currentGateway;
    }

    public function send(string $to, string $message, ?string $gateway = null): array
    {
        return $this->gateway($gateway)->send($to, $message);
    }

    public function sendPattern(string $to, string $patternCode, array $params, ?string $gateway = null): array
    {
        return $this->gateway($gateway)->sendPattern($to, $patternCode, $params);
    }

    public function getBalance(?string $gateway = null): float
    {
        return $this->gateway($gateway)->getBalance();
    }

    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }
}
