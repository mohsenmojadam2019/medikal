<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use Illuminate\Http\Request;

interface GatewayInterface
{
    public function getGatewayName(): string;

    public function initiate(Invoice $invoice, array $options = []): array;

    public function verify(Request $request): array;

    public function isAvailable(): bool;
}
