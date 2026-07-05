<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use Illuminate\Http\Request;

abstract class BaseGateway implements GatewayInterface
{
    protected string $name;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * دریافت نام درگاه
     */
    public function getGatewayName(): string
    {
        return $this->name;
    }

    /**
     * تنظیم نام درگاه
     */
    public function setGatewayName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * دریافت تنظیمات درگاه
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * بررسی اینکه آیا درگاه فعال است
     */
    public function isActive(): bool
    {
        return true;
    }

    abstract public function initiate(Invoice $invoice): array;
    abstract public function verify(Request $request): array;
}
