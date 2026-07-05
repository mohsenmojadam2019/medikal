<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use Illuminate\Http\Request;

class PaymentManager
{
    protected array $gateways = [];

    public function __construct(
        LocalGateway $localGateway,
        ZarinpalGateway $zarinpalGateway
    ) {
        $this->gateways = [
            'local' => $localGateway,
            'zarinpal' => $zarinpalGateway,
        ];
    }

    public function getAvailableGateways(): array
    {
        $available = [];
        foreach ($this->gateways as $name => $gateway) {
            if ($gateway->isActive()) {
                $available[] = $name;
            }
        }
        return $available;
    }

    public function getDefaultGateway(): string
    {
        return 'local';
    }

    public function isGatewayAvailable(string $gateway): bool
    {
        return isset($this->gateways[$gateway]) && $this->gateways[$gateway]->isActive();
    }

    public function initiate(string $gateway, Invoice $invoice): array
    {
        if (!$this->isGatewayAvailable($gateway)) {
            return [
                'success' => false,
                'message' => "درگاه {$gateway} در دسترس نیست",
            ];
        }

        return $this->gateways[$gateway]->initiate($invoice);
    }

    public function verify(string $gateway, Request $request): array
    {
        if (!$this->isGatewayAvailable($gateway)) {
            return [
                'success' => false,
                'message' => "درگاه {$gateway} در دسترس نیست",
            ];
        }

        return $this->gateways[$gateway]->verify($request);
    }

    public function getGateway(string $gateway): ?GatewayInterface
    {
        return $this->gateways[$gateway] ?? null;
    }
}
