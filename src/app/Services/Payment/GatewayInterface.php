<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;

interface GatewayInterface
{
    public function initiate(Order $order, array $options = []): array;

    public function verify(Request $request): array;
}
